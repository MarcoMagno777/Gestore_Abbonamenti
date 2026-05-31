import { Component, inject, signal, NgZone } from '@angular/core';
import { NavigationEnd, NavigationStart, Router, RouterOutlet } from '@angular/router';
import { NgIf } from '@angular/common';
import { filter } from 'rxjs';
import { Navbar } from './componenti/navbar/navbar';

@Component({
  standalone: true,
  selector: 'app-root',
  imports: [RouterOutlet, Navbar, NgIf],
  templateUrl: './app.html',
  styleUrl: './app.css'
})
export class App {
  private readonly router = inject(Router);
  private readonly ngZone = inject(NgZone);
  readonly showNavbar = signal(true);
  readonly loading = signal(true);
  readonly routeTransition = signal(false);

  constructor() {
    this.updateNavbar(this.router.url);
    this.router.events
      .pipe(filter((event): event is NavigationStart | NavigationEnd => event instanceof NavigationStart || event instanceof NavigationEnd))
      .subscribe((event) => {
        if (event instanceof NavigationStart) {
          this.routeTransition.set(true);
        }
        if (event instanceof NavigationEnd) {
          this.updateNavbar(event.urlAfterRedirects);
          this.ngZone.run(() => {
            setTimeout(() => this.routeTransition.set(false), 320);
          });
        }
      });

    this.ngZone.run(() => {
      setTimeout(() => this.loading.set(false), 900);
    });
  }

  private updateNavbar(url: string): void {
    this.showNavbar.set(url !== '/login');
  }
}
