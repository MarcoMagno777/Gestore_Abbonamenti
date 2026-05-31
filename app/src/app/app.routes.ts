import { Router, Routes } from '@angular/router';
import { inject } from '@angular/core';
import { Login } from './componenti/login/login';
import { Page } from './componenti/page/page';
import { AuthService } from './servizi/services';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },
  { path: 'login', component: Login },
  {
    path: '',
    canActivateChild: [
      () => {
        const auth = inject(AuthService);
        const router = inject(Router);
        if (auth.isAuthenticated()) {
          return true;
        }
        router.navigate(['/login']);
        return false;
      },
    ],
    children: [
      { path: 'dashboard', component: Page, data: { title: 'Dashboard' } },
      { path: 'abbonamenti', component: Page, data: { title: 'I miei abbonamenti' } },
      { path: 'profilo', component: Page, data: { title: 'Profilo' } },
      { path: 'pagamenti', component: Page, data: { title: 'Pagamenti' } },
      { path: 'dettagli', component: Page, data: { title: 'Dettagli' } },
      { path: 'abbonamenti/nuovo', component: Page, data: { title: 'Nuovo abbonamento' } },

      // Backward compatibility (old links with uppercase)
      { path: 'Profilo', redirectTo: 'profilo', pathMatch: 'full' },
      { path: 'Dettagli', redirectTo: 'dettagli', pathMatch: 'full' },
    ],
  },
  { path: '**', redirectTo: 'login' },
];
