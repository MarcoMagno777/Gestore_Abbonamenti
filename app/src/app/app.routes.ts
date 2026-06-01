import { Router, Routes } from '@angular/router';
import { inject } from '@angular/core';
import { map } from 'rxjs';
import { Login } from './componenti/login/login';
import { AuthService } from './servizi/services';
import { Dashboard } from './componenti/dashboard/dashboard';
import { Abbonamenti } from './componenti/abbonamenti/abbonamenti';
import { Profilo } from './componenti/profilo/profilo';
import { NuovoAbbonamento } from './componenti/nuovo-abbonamento/nuovo-abbonamento';
import { Admin } from './componenti/admin/admin';

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
        return auth.restoreSession().pipe(
          map((user) => user ? true : router.createUrlTree(['/login']))
        );
      },
    ],
    children: [
      { path: 'dashboard', component: Dashboard },
      { path: 'abbonamenti', component: Abbonamenti },
      { path: 'profilo', component: Profilo },
      { path: 'abbonamenti/nuovo', component: NuovoAbbonamento },
      {
        path: 'admin',
        component: Admin,
        canActivate: [
          () => {
            const auth = inject(AuthService);
            const router = inject(Router);
            if (auth.isAdmin()) {
              return true;
            }
            return auth.restoreSession().pipe(
              map(() => auth.isAdmin() ? true : router.createUrlTree(['/login']))
            );
          },
        ],
      },

      // Backward compatibility (old links with uppercase)
      { path: 'Profilo', redirectTo: 'profilo', pathMatch: 'full' },
    ],
  },
  { path: '**', redirectTo: 'login' },
];
