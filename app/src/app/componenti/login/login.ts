import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgIf } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService } from '../../servizi/services';

@Component({
  standalone: true,
  selector: 'app-login',
  imports: [NgIf, FormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  mode: 'login' | 'register' = 'login';
  errorMessage = '';
  successMessage = '';

  loginForm = {
    username: '',
    password: ''
  };

  registerForm = {
    username: '',
    email: '',
    password: ''
  };

  get isAuthenticated() {
    return this.authService.isAuthenticated;
  }

  get currentUser() {
    return this.authService.currentUser;
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

    this.authService.login(this.loginForm.username, this.loginForm.password).subscribe((success) => {
      if (success) {
        this.router.navigate(['/dashboard']);
      } else {
        this.errorMessage = 'Credenziali non valide. Verifica username e password.';
      }
    });
  }

  register(): void {
    this.clearMessages();

    if (!this.registerForm.username || !this.registerForm.email || !this.registerForm.password) {
      this.errorMessage = 'Compila tutti i campi del modulo di registrazione.';
      return;
    }

    this.authService.register(this.registerForm.username, this.registerForm.email, this.registerForm.password)
      .subscribe((success) => {
        if (success) {
          this.successMessage = 'Registrazione completata. Ora effettua il login.';
          this.mode = 'login';
          this.registerForm = { username: '', email: '', password: '' };
        } else {
          this.errorMessage = 'Questo username è già registrato.';
        }
      });
  }

  clearMessages(): void {
    this.errorMessage = '';
    this.successMessage = '';
  }
}

