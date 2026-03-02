import { Injectable } from '@angular/core';
import { BreakpointObserver, BreakpointState } from '@angular/cdk/layout';
import { Observable } from 'rxjs';

export const CUSTOM_BREAKPOINTS = {
    handset: '(max-width: 1024px)',
    web: '(min-width: 1025px)'
};

@Injectable({
    providedIn: 'root'
})
export class ResponsiveService {

    constructor(private breakpointObserver: BreakpointObserver) {}

    isHandset(): Observable<BreakpointState> {
        return this.breakpointObserver.observe([CUSTOM_BREAKPOINTS.handset]);
    }

    isWeb(): Observable<BreakpointState> {
        return this.breakpointObserver.observe([CUSTOM_BREAKPOINTS.web]);
    }
}