import { Routes } from '@angular/router';
import { Login } from './componenti/login/login';
import { Page } from './componenti/page/page';
import { authGuard } from './guards/auth.guard';
import { guestGuard } from './guards/guest.guard';

export const routes: Routes = [
  {
    path: 'login',
    component: Login,
    canActivate: [guestGuard],
  },
  {
    path: '',
    canActivate: [authGuard],
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      {
        path: 'dashboard',
        component: Page,
        data: { title: 'Dashboard' },
      },
      {
        path: 'abbonamenti',
        component: Page,
        data: { title: 'Miei Abbonamenti' },
      },
      {
        path: 'Profilo',
        component: Page,
        data: { title: 'Profilo' },
      },
      {
        path: 'pagamenti',
        component: Page,
        data: { title: 'Pagamenti' },
      },
      {
        path: 'Dettagli',
        component: Page,
        data: { title: 'Dettagli' },
      },
      {
        path: 'abbonamenti/nuovo',
        component: Page,
        data: { title: 'Nuovo abbonamento' },
      },
    ],
  },
  { path: '**', redirectTo: 'dashboard' },
];
