import { Component, OnInit, AfterViewInit, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ContextService } from 'sb-shared-lib';

@Component({
    selector: 'tasks',
    templateUrl: 'tasks.component.html',
    styleUrls: ['tasks.component.scss']
})
export class TasksComponent implements OnInit, AfterViewInit, OnDestroy {

    // rx subject for unsubscribing subscriptions on destroy
    private ngUnsubscribe = new Subject<void>();

    public ready: boolean = false;

    private default_descriptor: any = {
        // route: '/tasks',
        context: {
            entity: 'sale\\camp\\followup\\Task',
            view:   'list.default'
        }
    };

    constructor(
        private route: ActivatedRoute,
        private context: ContextService
    ) {}

    public ngOnDestroy() {
        console.debug('TasksComponent::ngOnDestroy');
        this.ngUnsubscribe.next();
        this.ngUnsubscribe.complete();
    }

    public ngAfterViewInit() {
        console.debug('TasksComponent::ngAfterViewInit');

        this.context.setTarget('#sb-container-task');

        this.context.change(this.default_descriptor);
    }

    public ngOnInit() {
        console.debug('TasksComponent::ngOnInit');

        this.context.ready.pipe(takeUntil(this.ngUnsubscribe)).subscribe( (ready:boolean) => {
            this.ready = ready;
        });

        this.route.params.pipe(takeUntil(this.ngUnsubscribe)).subscribe( async (params) => {
            if(this.ready) {
                this.default_descriptor.context.reset = true;
                this.context.change(this.default_descriptor);
            }
        });
    }
}
