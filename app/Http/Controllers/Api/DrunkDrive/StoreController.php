<?php

namespace App\Http\Controllers\Api\DrunkDrive;

use App\Http\Controllers\Api\Controller;
use App\Http\Responses\ApiResponses;
use App\Models\DrunkDrive\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Store::query()->orderBy('name');

        if (!$request->user()?->isAdmin() && !$request->user()?->isSuperAdmin()) {
            $query->where('is_active', true);
        } elseif ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return ApiResponses::ok('Stores fetched', $query->paginate(20));
    }

    public function show(Store $store): JsonResponse
    {
        return ApiResponses::ok('Store fetched', $store->load('creator:id,name,email'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:dd_stores,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        if (Store::where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        $store = Store::create([
            ...$validated,
            'slug' => $slug,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return ApiResponses::created('Store created', $store);
    }

    public function update(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('dd_stores', 'slug')->ignore($store->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $store->update($validated);

        return ApiResponses::ok('Store updated', $store->fresh());
    }

    public function destroy(Store $store): JsonResponse
    {
        $store->delete();

        return ApiResponses::ok('Store deleted', null);
    }
}
