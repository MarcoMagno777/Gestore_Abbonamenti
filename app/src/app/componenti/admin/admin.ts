import { Component, inject, OnInit } from '@angular/core';
import { CurrencyPipe, DatePipe, NgFor, NgIf } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Abbonamento, Account, AdminService, AuthService } from '../../servizi/services';

@Component({
  standalone: true,
  selector: 'app-admin',
  imports: [CurrencyPipe, DatePipe, NgFor, NgIf, FormsModule],
  templateUrl: './admin.html',
  styleUrl: './admin.css',
})
export class Admin implements OnInit {
  private readonly adminService = inject(AdminService);
  private readonly auth = inject(AuthService);

  readonly currentUser = this.auth.currentUser;
  accounts: Account[] = [];
  abbonamenti: Abbonamento[] = [];
  usersCount = 0;
  selectedAccountId: number | null = null;
  loading = true;
  errorMessage = '';
  successMessage = '';

  ngOnInit(): void {
    this.loadAdminData();
  }

  loadAdminData(): void {
    this.loading = true;
    this.errorMessage = '';

    this.adminService.getUsersCount().subscribe({
      next: (result) => {
        this.usersCount = result.total;
      },
    });

    this.adminService.getAccounts().subscribe({
      next: (accounts) => {
        this.accounts = accounts;
        this.selectedAccountId = this.deletableAccounts[0]?.id ?? null;
        this.loadAbbonamenti();
      },
      error: () => {
        this.errorMessage = 'Impossibile caricare gli account da AdminController.';
        this.loading = false;
      },
    });
  }

  get deletableAccounts(): Account[] {
    return this.accounts.filter((account) => account.role !== 'ADMIN');
  }

  deleteSelectedAccount(): void {
    if (!this.selectedAccountId) return;

    this.clearMessages();
    this.adminService.deleteAccount(this.selectedAccountId).subscribe({
      next: () => {
        this.successMessage = 'Utente eliminato.';
        this.scheduleMessageClear();
        this.loadAdminData();
      },
      error: () => {
        this.errorMessage = 'Eliminazione account non riuscita.';
      },
    });
  }

  deleteAllUsers(): void {
    this.clearMessages();
    this.adminService.deleteAllUsers().subscribe({
      next: () => {
        this.successMessage = 'Utenti eliminati. Admin mantenuto.';
        this.scheduleMessageClear();
        this.loadAdminData();
      },
      error: () => {
        this.errorMessage = 'Eliminazione utenti non riuscita.';
      },
    });
  }

  resetSelectedPassword(): void {
    if (!this.selectedAccountId) return;

    this.clearMessages();
    this.adminService.resetPassword(this.selectedAccountId).subscribe({
      next: (result) => {
        this.successMessage = `Password resettata a ${result.defaultPassword}.`;
        this.scheduleMessageClear();
      },
      error: () => {
        this.errorMessage = 'Reset password non riuscito.';
      },
    });
  }

  deleteAbbonamento(abbonamento: Abbonamento): void {
    this.clearMessages();
    this.adminService.deleteAbbonamento(abbonamento.id).subscribe({
      next: () => {
        this.successMessage = 'Abbonamento eliminato.';
        this.scheduleMessageClear();
        this.abbonamenti = this.abbonamenti.filter((item) => item.id !== abbonamento.id);
      },
      error: () => {
        this.errorMessage = 'Eliminazione abbonamento non riuscita.';
      },
    });
  }

  private clearMessages(): void {
    this.errorMessage = '';
    this.successMessage = '';
  }

  private scheduleMessageClear(): void {
    setTimeout(() => {
      this.successMessage = '';
    }, 3000);
  }

  private loadAbbonamenti(): void {
    this.adminService.getAbbonamenti().subscribe({
      next: (abbonamenti) => {
        this.abbonamenti = abbonamenti;
        this.loading = false;
      },
      error: () => {
        this.errorMessage = 'Impossibile caricare gli abbonamenti da AdminController.';
        this.loading = false;
      },
    });
  }
}
