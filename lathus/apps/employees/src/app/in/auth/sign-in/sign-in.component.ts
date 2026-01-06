import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { AuthService } from 'sb-shared-lib';
import { Router } from '@angular/router';
import { takeUntil } from 'rxjs/operators';
import { Subject } from 'rxjs';

@Component({
    selector: 'app-sign-in',
    templateUrl: 'sign-in.component.html',
    styleUrls: ['sign-in.component.scss']
})
export class SignInComponent implements OnInit  {

    public showConnectionErrorMsg: boolean = false;

    public form: FormGroup;

    private destroy$ = new Subject<void>();

    constructor(
        private formBuilder: FormBuilder,
        private auth: AuthService,
        private router: Router
    ) {}

    public ngOnInit() {
        this.auth.getObservable().pipe(takeUntil(this.destroy$)).subscribe(user => {
            if(user.id !== 0) {
                this.router.navigate(['/']);
            }
        });

        this.form = this.formBuilder.group({
            username: ['', [Validators.required, Validators.email]],
            password: ['', Validators.required]
        });
    }

    public ngOnDestroy() {
        this.destroy$.next();
        this.destroy$.complete();
    }

    public async signIn() {
        this.form.updateValueAndValidity();
        if(this.form.valid) {
            try {
                await this.auth.signIn(this.form.controls.username.value, this.form.controls.password.value);
                await this.router.navigate(['/']);
            } catch (e) {
                this.showConnectionErrorMsg = true;
            }
        }
        else {
            this.form.markAllAsTouched();
        }
    }
}
