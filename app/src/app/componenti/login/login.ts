import { Component, inject, OnInit } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../servizi/services';

@Component({
  standalone: true,
  selector: 'app-login',
  imports: [FormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login implements OnInit {
  private static readonly REMEMBER_USERNAME_KEY = 'submanager.rememberUsername';

  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  mode: 'login' | 'register' = 'login';
  rememberMe = false;
  loading = false;
  errorMessage = '';
  successMessage = '';

  loginForm = {
    username: '',
    password: '',
  };

  registerForm = {
    username: '',
    email: '',
    password: '',
  };

  ngOnInit(): void {
    const savedUsername = localStorage.getItem(Login.REMEMBER_USERNAME_KEY);
    if (savedUsername) {
      this.loginForm.username = savedUsername;
      this.rememberMe = true;
    }
  }

  toggleMode(): void {
    this.mode = this.mode === 'login' ? 'register' : 'login';
    this.clearMessages();
  }

  login(): void {
    this.clearMessages();

    if (!this.loginForm.username || !this.loginForm.password) {
      this.errorMessage = 'Inserisci username e password per accedere.';
      return;
    }

    this.loading = true;
    this.authService.login(this.loginForm.username, this.loginForm.password).subscribe((success) => {
      this.loading = false;

      if (success) {
        if (this.rememberMe) {
          localStorage.setItem(Login.REMEMBER_USERNAME_KEY, this.loginForm.username);
        } else {
          localStorage.removeItem(Login.REMEMBER_USERNAME_KEY);
        }

        this.router.navigate(['/dashboard']);
        return;
      }

      this.errorMessage = 'Credenziali non valide. Verifica username e password.';
    });
  }

  register(): void {
    this.clearMessages();

    if (!this.registerForm.username || !this.registerForm.email || !this.registerForm.password) {
      this.errorMessage = 'Compila tutti i campi del modulo di registrazione.';
      return;
    }

    this.loading = true;
    this.authService
      .register(this.registerForm.username, this.registerForm.email, this.registerForm.password)
      .subscribe((success) => {
        this.loading = false;

        if (success) {
          this.successMessage = 'Registrazione completata. Ora effettua il login.';
          this.mode = 'login';
          this.registerForm = { username: '', email: '', password: '' };
        } else {
          this.errorMessage = 'Registrazione non riuscita. Username già in uso o dati non validi.';
        }
      });
  }

  clearMessages(): void {
    this.errorMessage = '';
    this.successMessage = '';
  }
}
