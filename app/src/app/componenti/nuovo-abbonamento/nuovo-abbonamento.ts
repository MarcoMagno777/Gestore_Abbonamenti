import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NgIf } from '@angular/common';
import { Router } from '@angular/router';
import { AbbonamentoPayload, AuthService, SubscriptionsService } from '../../servizi/services';

@Component({
  standalone: true,
  selector: 'app-nuovo-abbonamento',
  imports: [FormsModule, NgIf],
  templateUrl: './nuovo-abbonamento.html',
  styleUrl: './nuovo-abbonamento.css',
})
export class NuovoAbbonamento {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly subscriptionsService = inject(SubscriptionsService);

  saving = false;
  errorMessage = '';

  form: AbbonamentoPayload = {
    nome: '',
    descrizione: '',
    data_sottoscrizione: '',
    data_scadenza: '',
    costo: 0,
    id_account: 0,
  };

  saveSubscription(): void {
    const accountId = this.auth.currentAccountId();
    this.errorMessage = '';

    if (accountId && Number.isFinite(accountId) && accountId > 0) {
      this.form.id_account = accountId;
    }

    if (!this.form.id_account) {
      this.errorMessage = 'ID account non disponibile nella sessione. Effettua di nuovo l’accesso.';
      return;
    }

    if (!this.form.nome || !this.form.data_sottoscrizione || !this.form.data_scadenza || !this.form.costo) {
      this.errorMessage = 'Compila tutti i campi obbligatori.';
      return;
    }

    this.saving = true;
    const payload: AbbonamentoPayload = {
      ...this.form,
      costo: Number(this.form.costo),
      descrizione: this.form.descrizione?.trim() || null,
    };

    this.subscriptionsService.createSubscription(payload).subscribe({
      next: () => {
        this.router.navigate(['/abbonamenti']);
      },
      error: (error: HttpErrorResponse) => {
        this.errorMessage = this.getCreateErrorMessage(error);
        this.saving = false;
      },
    });
  }

  private getCreateErrorMessage(error: HttpErrorResponse): string {
    if (error.status === 0) {
      return 'Backend non raggiungibile. Controlla che il servizio PHP sia avviato su localhost:8080.';
    }

    if (error.status === 404) {
      return 'Rotta backend per gli abbonamenti non trovata.';
    }

    if (error.status >= 400) {
      return 'Creazione non riuscita: verifica che l’account esista nel database.';
    }

    return 'Creazione non riuscita.';
  }
}
