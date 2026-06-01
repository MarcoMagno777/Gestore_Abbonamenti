import { HttpClient } from '@angular/common/http';
import { Injectable, signal, computed, inject } from '@angular/core';
import { Observable, of, tap, catchError, throwError, map } from 'rxjs';

export interface User {
  id: string;
  accountId?: number;
  role: 'USER' | 'ADMIN';
  name: string;
  email?: string;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private static readonly REMEMBER_USERNAME_KEY = 'submanager.rememberedUsername.v1';

  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'http://localhost:8080/api';
  private user = signal<User | null>(null);

  public isAuthenticated = computed(() => this.user() !== null);
  public isAdmin = computed(() => this.user()?.role === 'ADMIN');
  public currentUser = this.user.asReadonly();
  public currentAccountId = computed(() => {
    const current = this.user();
    if (!current) return null;
    return current.accountId ?? Number(current.id);
  });

  login(username: string, password: string, rememberUsername: boolean): Observable<boolean> {
    return this.http.post<User>(`${this.apiUrl}/auth/login`, { username, password }, { withCredentials: true }).pipe(
      tap((user) => {
        this.user.set(user);
        this.persistRememberedUsername(rememberUsername ? username : '');
      }),
      map(() => true),
      catchError((error) => {
        this.user.set(null);
        return error.status === 401 ? of(false) : throwError(() => error);
      })
    );
  }

  register(username: string, email: string, password: string): Observable<boolean> {
    return this.http.post<User>(`${this.apiUrl}/auth/register`, { username, email, password }, { withCredentials: true }).pipe(
      tap((user) => this.user.set(user)),
      map(() => true),
      catchError((error) => error.status === 409 ? of(false) : throwError(() => error))
    );
  }

  logout(): void {
    this.http.post(`${this.apiUrl}/auth/logout`, {}, { withCredentials: true }).subscribe({
      next: () => this.user.set(null),
      error: () => this.user.set(null),
    });
  }

  restoreSession(): Observable<User | null> {
    return this.http.get<User>(`${this.apiUrl}/auth/me`, { withCredentials: true }).pipe(
      tap((user) => this.user.set(user)),
      catchError(() => {
        this.user.set(null);
        return of(null);
      })
    );
  }

  getRememberedUsername(): string {
    try {
      return localStorage.getItem(AuthService.REMEMBER_USERNAME_KEY) ?? '';
    } catch {
      return '';
    }
  }

  private persistRememberedUsername(username: string): void {
    try {
      if (username) {
        localStorage.setItem(AuthService.REMEMBER_USERNAME_KEY, username);
      } else {
        localStorage.removeItem(AuthService.REMEMBER_USERNAME_KEY);
      }
    } catch {
      // ignore storage failures
    }
  }
}

export interface Account {
  id: number;
  username: string;
  email: string;
  role?: 'USER' | 'ADMIN';
}

export interface Abbonamento {
  id: number;
  nome: string;
  descrizione: string | null;
  data_sottoscrizione: string;
  data_scadenza: string;
  costo: number;
  id_account: number;
}

export type AbbonamentoPayload = Omit<Abbonamento, 'id'>;

export interface DashboardSummary {
  activeSubscriptions: number;
  monthlyTotal: number;
  nextRenewal: Abbonamento | null;
}

@Injectable({ providedIn: 'root' })
export class SubscriptionsService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'http://localhost:8080/api';

  getDashboard(userId: string | number): Observable<DashboardSummary> {
    return this.http.get<DashboardSummary>(`${this.apiUrl}/dashboard`, { withCredentials: true });
  }

  getSubscriptions(userId: string | number): Observable<Abbonamento[]> {
    return this.http.get<Abbonamento[]>(`${this.apiUrl}/abbonamenti`, { withCredentials: true }).pipe(
      catchError((error) => {
        if (error.status !== 404) return throwError(() => error);
        return this.http.get<Abbonamento[]>(`${this.apiUrl}/abbonamento`, { withCredentials: true });
      })
    );
  }

  createSubscription(payload: AbbonamentoPayload): Observable<Abbonamento> {
    return this.http.post<Abbonamento>(`${this.apiUrl}/abbonamenti`, payload, { withCredentials: true }).pipe(
      catchError((error) => {
        if (error.status !== 404) return throwError(() => error);
        return this.http.post<Abbonamento>(`${this.apiUrl}/abbonamento`, payload, { withCredentials: true });
      })
    );
  }

  updateSubscription(id: number, payload: Partial<AbbonamentoPayload>): Observable<Abbonamento> {
    return this.http.put<Abbonamento>(`${this.apiUrl}/abbonamenti/${id}`, payload, { withCredentials: true });
  }

  deleteSubscription(id: number): Observable<{ success: boolean }> {
    return this.http.delete<{ success: boolean }>(`${this.apiUrl}/abbonamenti/${id}`, { withCredentials: true });
  }

  getProfile(userId: string | number): Observable<Account> {
    return this.http.get<Account>(`${this.apiUrl}/account/${userId}`, { withCredentials: true });
  }
}

@Injectable({ providedIn: 'root' })
export class AdminService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = 'http://localhost:8080/api/admin';

  getAccounts(): Observable<Account[]> {
    return this.http.get<Account[]>(`${this.apiUrl}/accounts`, { withCredentials: true });
  }

  getAbbonamenti(): Observable<Abbonamento[]> {
    return this.http.get<Abbonamento[]>(`${this.apiUrl}/abbonamenti`, { withCredentials: true });
  }

  getUsersCount(): Observable<{ total: number }> {
    return this.http.get<{ total: number }>(`${this.apiUrl}/users/count`, { withCredentials: true });
  }

  deleteAccount(id: number): Observable<{ success: boolean }> {
    return this.http.delete<{ success: boolean }>(`${this.apiUrl}/accounts/${id}`, { withCredentials: true });
  }

  deleteAllUsers(): Observable<{ success: boolean }> {
    return this.http.delete<{ success: boolean }>(`${this.apiUrl}/users`, { withCredentials: true });
  }

  resetPassword(id: number): Observable<{ success: boolean; defaultPassword: string }> {
    return this.http.put<{ success: boolean; defaultPassword: string }>(`${this.apiUrl}/accounts/${id}/reset-password`, {}, { withCredentials: true });
  }

  deleteAbbonamento(id: number): Observable<{ success: boolean }> {
    return this.http.delete<{ success: boolean }>(`${this.apiUrl}/abbonamenti/${id}`, { withCredentials: true });
  }
}
