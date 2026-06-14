import { ensureApiToken } from "./shared/token.js";

window.ensureApiToken = ensureApiToken;

import "./pages/index.js";
import.meta.glob("../css/pages/**/*.css", { eager: true });
