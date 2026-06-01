import { Component, inject, OnInit } from '@angular/core';
import { NgIf } from '@angular/common';
import { Account, AuthService, SubscriptionsService } from '../../servizi/services';

@Component({
  standalone: true,
  selector: 'app-profilo',
  imports: [NgIf],
  templateUrl: './profilo.html',
  styleUrl: './profilo.css',
})
export class Profilo implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly subscriptionsService = inject(SubscriptionsService);

  profile: Account | null = null;
  fallbackProfile: Account = {
    id: 0,
    username: '',
    email: '',
  };

  loading = true;
  errorMessage = '';

  ngOnInit(): void {
    const currentUser = this.auth.currentUser();
    const userId = currentUser?.accountId ?? currentUser?.id;
    if (!currentUser || !userId) return;

    this.fallbackProfile = {
      id: currentUser.accountId ?? 0,
      username: currentUser.name,
      email: currentUser.email ?? '',
    };

    this.subscriptionsService.getProfile(userId).subscribe({
      next: (profile) => {
        this.profile = profile;
        this.loading = false;
      },
      error: () => {
        this.profile = this.fallbackProfile;
        this.errorMessage = 'Profilo backend non disponibile, mostro le informazioni della sessione.';
        this.loading = false;
      },
    });
  }
}
