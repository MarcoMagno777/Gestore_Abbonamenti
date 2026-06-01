import { Component, inject, OnInit } from '@angular/core';
import { CurrencyPipe, DatePipe, NgIf } from '@angular/common';
import { RouterLink } from '@angular/router';
import { AuthService, DashboardSummary, SubscriptionsService } from '../../servizi/services';

@Component({
  standalone: true,
  selector: 'app-dashboard',
  imports: [CurrencyPipe, DatePipe, NgIf, RouterLink],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
})
export class Dashboard implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly subscriptionsService = inject(SubscriptionsService);

  readonly currentUser = this.auth.currentUser;
  summary: DashboardSummary | null = null;
  loading = true;
  errorMessage = '';

  ngOnInit(): void {
    this.loadDashboard();
  }

  loadDashboard(): void {
    const userId = this.currentUser()?.accountId ?? this.currentUser()?.id;
    if (!userId) return;

    this.loading = true;
    this.errorMessage = '';

    this.subscriptionsService.getDashboard(userId).subscribe({
      next: (summary) => {
        this.summary = summary;
        this.loading = false;
      },
      error: () => {
        this.errorMessage = 'Impossibile caricare la dashboard dal backend.';
        this.loading = false;
      },
    });
  }
}
