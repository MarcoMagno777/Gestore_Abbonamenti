import { Component, inject, OnInit } from '@angular/core';
import { CurrencyPipe, DatePipe, NgFor, NgIf } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Abbonamento, AuthService, SubscriptionsService } from '../../servizi/services';

@Component({
  standalone: true,
  selector: 'app-abbonamenti',
  imports: [CurrencyPipe, DatePipe, NgFor, NgIf, RouterLink],
  templateUrl: './abbonamenti.html',
  styleUrl: './abbonamenti.css',
})
export class Abbonamenti implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly subscriptionsService = inject(SubscriptionsService);

  subscriptions: Abbonamento[] = [];
  loading = true;
  errorMessage = '';

  ngOnInit(): void {
    this.loadSubscriptions();
  }

  loadSubscriptions(): void {
    const userId = this.auth.currentUser()?.accountId ?? this.auth.currentUser()?.id;
    if (!userId) return;

    this.loading = true;
    this.errorMessage = '';
    this.subscriptionsService.getSubscriptions(userId).subscribe({
      next: (subscriptions) => {
        this.subscriptions = subscriptions;
        this.loading = false;
      },
      error: () => {
        this.errorMessage = 'Impossibile caricare gli abbonamenti.';
        this.loading = false;
      },
    });
  }

  deleteSubscription(subscription: Abbonamento): void {
    this.subscriptionsService.deleteSubscription(subscription.id).subscribe({
      next: () => {
        this.subscriptions = this.subscriptions.filter((item) => item.id !== subscription.id);
      },
      error: () => {
        this.errorMessage = 'Eliminazione non riuscita.';
      },
    });
  }
}
