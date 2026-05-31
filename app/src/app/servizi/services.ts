import { Injectable, signal, computed } from '@angular/core';
import { Observable, of, delay, tap } from 'rxjs';

export interface User {
  id: string;
  role: 'USER' | 'ADMIN';
  name: string;
}

interface RegisteredUser {
  email: string;
  password: string;
  role: 'USER' | 'ADMIN';
  name: string;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private static readonly STORAGE_USER_KEY = 'submanager.currentUser.v1';
  private static readonly STORAGE_USERS_KEY = 'submanager.registeredUsers.v1';

  private user = signal<User | null>(null);
  private users = new Map<string, RegisteredUser>([
    [
      'admin',
      {
        email: 'admin@submanager.local',
        password: 'admin123',
        role: 'ADMIN',
        name: 'Amministratore'
      }
    ]
  ]);

  public isAuthenticated = computed(() => this.user() !== null);
  public isAdmin = computed(() => this.user()?.role === 'ADMIN');
  public currentUser = this.user.asReadonly();

  constructor() {
    this.hydrateUsers();
    this.hydrateUser();
  }

  login(username: string, password: string): Observable<boolean> {
    const stored = this.users.get(username.toLowerCase());
    const success = stored?.password === password;

    return of(success).pipe(
      delay(600),
      tap((result) => {
        if (result && stored) {
          const nextUser: User = {
            id: username,
            role: stored.role,
            name: stored.name
          };
          this.user.set(nextUser);
          this.persistUser(nextUser);
        }
      })
    );
  }

  register(username: string, email: string, password: string): Observable<boolean> {
    const normalized = username.toLowerCase();

    if (this.users.has(normalized)) {
      return of(false).pipe(delay(600));
    }

    const newUser: RegisteredUser = {
      email,
      password,
      role: 'USER',
      name: username
    };

    this.users.set(normalized, newUser);
    this.persistUsers();

    const nextUser: User = {
      id: normalized,
      role: newUser.role,
      name: newUser.name
    };

    this.user.set(nextUser);
    this.persistUser(nextUser);

    return of(true).pipe(delay(600));
  }

  logout(): void {
    this.user.set(null);
    this.persistUser(null);
  }

  private hydrateUser(): void {
    try {
      const raw = localStorage.getItem(AuthService.STORAGE_USER_KEY);
      if (!raw) return;
      const parsed = JSON.parse(raw) as User | null;
      if (!parsed) return;
      if (!parsed.id || !parsed.role || !parsed.name) return;
      this.user.set(parsed);
    } catch {
      // ignore corrupted storage
    }
  }

  private hydrateUsers(): void {
    try {
      const raw = localStorage.getItem(AuthService.STORAGE_USERS_KEY);
      if (!raw) return;
      const parsed = JSON.parse(raw) as Array<[string, RegisteredUser]>;
      if (!Array.isArray(parsed)) return;
      for (const entry of parsed) {
        if (!Array.isArray(entry) || entry.length !== 2) continue;
        const [key, value] = entry;
        if (!key || !value?.password || !value?.email || !value?.role || !value?.name) continue;
        if (key.toLowerCase() === 'admin') continue; // keep built-in admin
        this.users.set(String(key).toLowerCase(), {
          email: value.email,
          password: value.password,
          role: value.role,
          name: value.name,
        });
      }
    } catch {
      // ignore corrupted storage
    }
  }

  private persistUser(user: User | null): void {
    try {
      if (user) {
        localStorage.setItem(AuthService.STORAGE_USER_KEY, JSON.stringify(user));
      } else {
        localStorage.removeItem(AuthService.STORAGE_USER_KEY);
      }
    } catch {
      // ignore storage failures (private mode, quota, etc.)
    }
  }

  private persistUsers(): void {
    try {
      const entries = Array.from(this.users.entries()).filter(([k]) => k !== 'admin');
      localStorage.setItem(AuthService.STORAGE_USERS_KEY, JSON.stringify(entries));
    } catch {
      // ignore storage failures
    }
  }
}
