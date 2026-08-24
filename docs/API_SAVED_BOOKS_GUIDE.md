# Book Save & Bookmark API Guide

This guide documents the new book save/bookmark functionality and offline reading features for the e-library backend.

## Overview

Users can now:
1. **Save (bookmark) books** for later reading
2. **Add saved books to playlists** (existing or new ones created on-the-fly)
3. **Access offline cache** of saved books for reading without internet
4. **Manage notes** on saved books

All operations properly track `user_id` in the database to ensure each user's saved books are isolated.

## Database Schema

### `user_saved_books` Table

```sql
CREATE TABLE user_saved_books (
  id UUID PRIMARY KEY,
  user_id UUID NOT NULL,
  book_id UUID NOT NULL,
  notes TEXT NULLABLE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_book (user_id, book_id),
  INDEX idx_user_id (user_id),
  INDEX idx_book_id (book_id)
);
```

**Key Features:**
- UUID primary key and foreign keys (Laravel convention)
- Unique constraint ensures each user can save a book only once
- Cascading deletes: removing user or book removes all related saves
- Indexes on `user_id` and `book_id` for query performance
- Optional `notes` field for user's personal notes about the book

## Models & Relationships

### UserSavedBook Model
```php
class UserSavedBook extends Model {
    use HasUuids;
    
    protected $fillable = ['user_id', 'book_id', 'notes'];
    
    public function user() { return $this->belongsTo(User::class); }
    public function book() { return $this->belongsTo(Books::class); }
}
```

### User Model (Updated)
```php
public function savedBooks() {
    return $this->hasMany(UserSavedBook::class, 'user_id', 'id');
}
```

### Books Model (Updated)
```php
public function savedByUsers() {
    return $this->hasMany(UserSavedBook::class, 'book_id', 'id');
}
```

## API Endpoints

All endpoints require authentication with Sanctum token (`auth:sanctum`).

### 1. Get User's Saved Books

**Endpoint:** `GET /api/v1/me/saved-books`

**Query Parameters:**
- `page` (integer, optional): Page number for pagination (default: 1)
- `per_page` (integer, optional): Items per page, max 50 (default: 15)

**Response:**
```json
{
  "message": "Saved books fetched successfully",
  "data": {
    "data": [
      {
        "id": "uuid",
        "title": "Book Title",
        "description": "Book description",
        "price": 9.99,
        "author_id": "uuid",
        "saved_at": "2025-01-15T10:30:00Z",
        "notes": "Great technical reference",
        "user_has_liked": false,
        "can_purchase": true
      }
    ],
    "meta": {
      "current_page": 1,
      "total": 25,
      "per_page": 15
    }
  }
}
```

**Uses Cache:** Yes (TTL: 1 hour)

---

### 2. Save a Book

**Endpoint:** `POST /api/v1/books/{book}/save`

**Request Body:**
```json
{
  "notes": "Optional personal notes about this book"
}
```

**Response (201 Created):**
```json
{
  "message": "Book saved successfully",
  "data": {
    "id": "uuid",
    "title": "Book Title",
    "saved": true,
    "user_has_liked": false
  }
}
```

**Response (200 if Already Saved):**
```json
{
  "message": "Book already saved",
  "data": {
    "saved": true,
    "book_id": "uuid",
    "notes": "Previous notes if any"
  }
}
```

**Status Codes:**
- `201`: Book successfully saved
- `200`: Book was already saved
- `401`: Unauthorized
- `404`: Book not found

---

### 3. Unsave a Book

**Endpoint:** `POST /api/v1/books/{book}/unsave`

**Request Body:** (empty)

**Response:**
```json
{
  "message": "Book unsaved successfully",
  "data": {
    "saved": false
  }
}
```

**Status Codes:**
- `200`: Successfully unsaved
- `401`: Unauthorized
- `404`: Book not found

---

### 4. Toggle Save Status

**Endpoint:** `POST /api/v1/books/{book}/save/toggle`

**Request Body:** (empty)

**Response (if saved):**
```json
{
  "message": "Book saved",
  "data": {
    "saved": true
  }
}
```

**Response (if unsaved):**
```json
{
  "message": "Book unsaved",
  "data": {
    "saved": false
  }
}
```

---

### 5. Check Save Status

**Endpoint:** `GET /api/v1/books/{book}/saved`

**Response:**
```json
{
  "message": "Save status fetched",
  "data": {
    "saved": true
  }
}
```

---

### 6. Add Saved Book to Playlist (Create or Existing)

**Endpoint:** `POST /api/v1/books/{book}/add-to-playlist`

**Request Body - Add to Existing Playlist:**
```json
{
  "playlist_id": "uuid-of-existing-playlist"
}
```

**Request Body - Create New Playlist and Add:**
```json
{
  "playlist_name": "My New Reading List"
}
```

**Response (201 Created):**
```json
{
  "message": "Book added to playlist",
  "data": {
    "id": "uuid",
    "name": "My New Reading List",
    "description": null,
    "user_id": "uuid",
    "is_owner": true,
    "can_edit": true,
    "is_public": false,
    "books_count": 1,
    "books": [
      {
        "id": "uuid",
        "title": "Book Title",
        "sort_order": 0,
        "pivot": {
          "playlist_id": "uuid",
          "book_id": "uuid",
          "sort_order": 0
        }
      }
    ]
  }
}
```

**Error Responses:**

If trying to add to someone else's playlist (403):
```json
{
  "message": "You do not have permission to add books to this playlist",
  "data": null
}
```

If book already in playlist (422):
```json
{
  "message": "Book is already in this playlist",
  "data": {
    "playlist_id": "uuid",
    "book_id": "uuid"
  }
}
```

**Status Codes:**
- `201`: Book added to playlist
- `401`: Unauthorized
- `403`: Permission denied
- `404`: Book or playlist not found
- `422`: Validation error (book already in playlist, or neither playlist_id nor playlist_name provided)

---

### 7. Get Offline Cache for All Saved Books

**Endpoint:** `GET /api/v1/offline-cache`

**Purpose:** Fetch metadata for all saved books suitable for offline storage (mobile apps, PWAs)

**Response:**
```json
{
  "message": "Offline cache data prepared",
  "data": {
    "user_id": "uuid",
    "synced_at": "2025-01-15T10:30:00Z",
    "total_books": 3,
    "cache_ttl": 3600,
    "books": [
      {
        "id": "uuid",
        "title": "Offline Book 1",
        "description": "Description",
        "price": 9.99,
        "has_pdf": true,
        "pdf_file": "/path/to/pdf-file.pdf",
        "preview_path": "/path/to/preview.pdf",
        "saved_at": "2025-01-15T08:00:00Z",
        "notes": "User's notes"
      },
      {
        "id": "uuid",
        "title": "Offline Book 2",
        "description": "Another book",
        "price": 0,
        "has_pdf": true,
        "pdf_file": "/path/to/free-book.pdf",
        "preview_path": null,
        "saved_at": "2025-01-15T09:00:00Z",
        "notes": null
      }
    ]
  }
}
```

**Uses Cache:** Yes (TTL: 1 hour)

---

### 8. Get Offline Cache for Single Book

**Endpoint:** `GET /api/v1/offline-cache/book/{book}`

**Purpose:** Fetch metadata for a single saved book for offline access

**Response:**
```json
{
  "message": "Book offline data fetched",
  "data": {
    "id": "uuid",
    "title": "Book Title",
    "description": "Description",
    "price": 9.99,
    "has_pdf": true,
    "pdf_file": "/path/to/book.pdf",
    "preview_path": "/path/to/preview.pdf",
    "saved_at": "2025-01-15T10:30:00Z",
    "notes": "User's notes",
    "synced_at": "2025-01-15T10:30:00Z"
  }
}
```

**Status Codes:**
- `200`: Successfully fetched
- `401`: Unauthorized
- `404`: Book not saved by user

**Uses Cache:** Yes (TTL: 1 hour per book)

---

## Implementation Details

### Controller: BookSaveController

Located at: `app/Http/Controllers/Api/BookSaveController.php`

**Methods:**
- `index()` - List user's saved books with pagination and caching
- `save()` - Save a book with optional notes
- `unsave()` - Remove a book from saves
- `toggle()` - Toggle save status
- `isSaved()` - Check if book is saved
- `addToPlaylist()` - Add to existing or create new playlist
- `offlineCache()` - Get cache metadata for all saved books
- `offlineBook()` - Get cache metadata for single book
- `invalidateUserCache()` - Clear cache keys after mutations

**Cache Strategy:**
- List view: `user.{user_id}.saved_books.list.page_{page}`
- Offline all: `user.{user_id}.offline_books`
- Offline single: `user.{user_id}.offline_book.{book_id}`
- TTL: 3600 seconds (1 hour)
- Invalidated on: save, unsave, toggle, add-to-playlist

### Authorization & User Tracking

All endpoints use the authenticated user from `$request->user()`:

```php
$user = $request->user(); // From Sanctum auth

// Save operation
UserSavedBook::create([
    'user_id' => $user->id,      // ✓ Tracked
    'book_id' => $book->id,
    'notes' => $notes
]);

// Playlist creation
$playlist = Playlist::create([
    'user_id' => $user->id,      // ✓ User owns playlist
    'name' => $playlistName,
]);
```

**Authorization Checks:**
- User can only save books to their own saves
- User can only add books to playlists they own (checked via `$playlist->isOwnedBy($user)`)
- User can only view their own saved books and offline cache

---

## Usage Examples

### Example 1: Save a Book and Get Status

```bash
# Save book
curl -X POST http://localhost:8000/api/v1/books/uuid/save \
  -H "Authorization: Bearer token" \
  -H "Content-Type: application/json" \
  -d '{"notes": "Great programming guide"}'

# Check if saved
curl -X GET http://localhost:8000/api/v1/books/uuid/saved \
  -H "Authorization: Bearer token"
```

### Example 2: Save Book and Create Playlist

```bash
curl -X POST http://localhost:8000/api/v1/books/uuid/add-to-playlist \
  -H "Authorization: Bearer token" \
  -H "Content-Type: application/json" \
  -d '{"playlist_name": "Technical Books"}'
```

### Example 3: Get Offline Books for Mobile App

```bash
# Get all saved books metadata
curl -X GET http://localhost:8000/api/v1/offline-cache \
  -H "Authorization: Bearer token"

# Mobile app stores this data locally, provides offline access
# Later syncs by calling the same endpoint to check for new saves
```

### Example 4: Workflow - Save, View, Add to Playlist, View Offline

```bash
# 1. User saves a book
POST /api/v1/books/{bookId}/save

# 2. User checks their saved books
GET /api/v1/me/saved-books

# 3. User decides to organize - creates playlist and adds
POST /api/v1/books/{bookId}/add-to-playlist
  { "playlist_name": "Reading Queue 2025" }

# 4. Mobile app prepares offline reading
GET /api/v1/offline-cache/book/{bookId}

# 5. User removes save later
POST /api/v1/books/{bookId}/unsave
```

---

## Caching Strategy for Offline Reading

### When Cache is Generated
1. **User saves a book** → `invalidateUserCache()` clears old cache
2. **Subsequent reads** → Cache::remember() generates and stores for 1 hour
3. **User unsaves** → Cache invalidated, prevents stale data

### For Mobile Clients
```javascript
// Client fetches offline data
const offlineData = await fetch('/api/v1/offline-cache', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// Store in IndexedDB or localStorage
indexedDB.open('elbookstore').then(db => {
  db.put('saved_books', offlineData);
});

// Later, read from local storage without network
const cachedBooks = await indexedDB.get('saved_books');
```

### Cache Invalidation
- **Automatic TTL**: 1 hour expiry
- **Manual**: Triggered on any save/unsave/playlist operation
- **Client-side**: App should periodically sync by calling offline-cache endpoint

---

## Test Coverage

**File:** `tests/Feature/Feature/UserSavedBooksTest.php`

**Test Count:** 15 tests, 44 assertions, all passing ✅

**Tests Include:**
1. Save book basic operation
2. Save book with notes
3. Duplicate save prevention
4. Unsave book
5. Toggle save status
6. Check save status
7. List user's saved books
8. Add to existing playlist
9. Create new playlist and add
10. Permission check (cannot add to others' playlists)
11. Get offline cache for all books
12. Get offline cache for single book
13. User → SavedBooks relationship
14. Book → SavedByUsers relationship
15. User ID tracking verification

---

## Integration with Existing Features

### Related Models & Relationships
- **User** → `playlistLikes()`, `bookLikes()` (existing)
- **User** → `savedBooks()` (new)
- **Playlist** → accepts books via `addToPlaylist()`
- **Books** → `savedByUsers()` (new)

### Middleware & Authorization
- All endpoints use `auth:sanctum` middleware
- No `RoleMiddleware` required - any authenticated user can save books
- User ownership validated in `addToPlaylist()` via `Playlist::isOwnedBy()`

### Response Format
- Uses existing `BookApiPresenter::toArray()` for book formatting
- Uses existing `PlaylistApiPresenter::toArray()` for playlist formatting
- Consistent error response format with message + data

---

## Performance Considerations

### Database
- Unique constraint prevents duplicate saves
- Indexes on user_id and book_id optimize queries
- Cascading deletes ensure referential integrity

### Caching
- Reduces database hits for frequently accessed saved books
- 1-hour TTL balances freshness with performance
- Per-page caching allows pagination without full list queries

### Future Optimizations
- Batch API endpoint to save/remove multiple books at once
- Pagination with cursor-based sorting
- WebSocket support for real-time sync across devices

---

## Migration

The migration `2026_08_24_143234_create_user_saved_books_table` is already applied. To rollback:

```bash
php artisan migrate:rollback
```

To migrate fresh:

```bash
php artisan migrate
```

---

## Summary

✅ **Completed Features:**
- Save/unsave books with user_id tracking
- Optional notes on saved books
- Unique constraint prevents duplicate saves
- Add saved books to existing playlists
- Create new playlists on-the-fly when adding books
- Offline cache for reading without internet
- Permission checks for playlist operations
- Comprehensive test coverage (15 tests passing)
- Model relationships (User → SavedBooks, Books → SavedByUsers)
- Cache layer for performance
- Proper authorization using authenticated user

✅ **Database:**
- user_saved_books table created with UUID, FKs, unique constraint, indexes
- Relationships defined in User, Books, UserSavedBook models

✅ **API Routes:**
All routes registered under `auth:sanctum` middleware in routes/api.php

✅ **Tests:**
All 15 tests passing with 44 assertions validating user tracking, relationships, offline cache, and playlist operations
