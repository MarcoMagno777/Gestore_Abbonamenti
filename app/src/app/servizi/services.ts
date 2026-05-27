import { Injectable, signal, computed } from '@angular/core';
import { Observable, of, delay, tap } from 'rxjs';

export interface User {
  id: string;
  role: 'USER' | 'ADMIN';
  name: string;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private user = signal<User | null>(null);
  
  public isAuthenticated = computed(() => this.user() !== null);
  public currentUser = this.user.asReadonly();

  // ✅ Ritorna Observable<boolean> per coerenza con il routing
  login(role: 'USER' | 'ADMIN'): Observable<boolean> {
    const mockUser: User = {
      id: crypto.randomUUID?.() ?? `user-${Date.now()}`,
      role,
      name: role === 'ADMIN' ? 'Amministratore' : 'Utente'
    };

    return of(true).pipe(
      delay(600), // Simula chiamata di rete
      tap(() => this.user.set(mockUser))
    );
  }

  logout(): void {
    this.user.set(null);
  }
}