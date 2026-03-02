import { Component, OnInit } from '@angular/core';
import { Router, ActivatedRoute} from '@angular/router';
import { AuthService } from 'src/app/_services/AuthService';


/**
 * This Component is called when a nonce_token is present in the URL.
 * It uses it to attempt to sign the user in. In case of success, it redirects to root URL.
 */
@Component({
    selector: 'auth',
    templateUrl: 'auth.component.html',
    styleUrls: ['auth.component.scss']
})
export class AuthComponent implements OnInit  {
    public ready: boolean = false;
    public has_auth_error: boolean = false;

    constructor(
        private route: ActivatedRoute,
        private auth: AuthService,
        private router: Router
    ) {}

    public async ngOnInit() {
        console.debug('AuthComponent::ngOnInit');
        // extract nonce_token from URL, if present
        this.route.params.subscribe(async params => {
                if(params.hasOwnProperty('nonce_token')) {
                    try {
                        await this.auth.signIn(params.nonce_token);
                        // redirect to root URL
                        this.router.navigate(['/']);
                    }
                    catch(response) {
                        this.has_auth_error = true;
                        this.ready = true;
                    }
                }
            });
    }

}
