import { Component, OnInit, AfterViewInit, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { ContextService } from 'sb-shared-lib';

@Component({
    selector: 'task',
    templateUrl: 'task.component.html',
    styleUrls: ['task.component.scss']
})
export class TaskComponent implements OnInit, AfterViewInit, OnDestroy {

    // rx subject for unsubscribing subscriptions on destroy
    private ngUnsubscribe = new Subject<void>();

    public ready: boolean = false;

    private default_descriptor: any = {
        // route: '/task/object.id',
        context: {
            entity: 'sale\\booking\\followup\\Task',
            view:   'form.default'
        }
    };

    private task_id: number = 0;

    constructor(
        private route: ActivatedRoute,
        private context: ContextService
    ) {}

    public ngOnDestroy() {
        console.debug('TaskComponent::ngOnDestroy');
        this.ngUnsubscribe.next();
        this.ngUnsubscribe.complete();
    }

    public ngAfterViewInit() {
        console.debug('TaskComponent::ngAfterViewInit');

        this.context.setTarget('#sb-container-task');

        this.default_descriptor.context.domain = ["id", "=", this.task_id];
        this.context.change(this.default_descriptor);
    }

    public ngOnInit() {
        console.debug('TaskComponent::ngOnInit');

        this.context.ready.pipe(takeUntil(this.ngUnsubscribe)).subscribe( (ready:boolean) => {
            this.ready = ready;
        });

        this.route.params.pipe(takeUntil(this.ngUnsubscribe)).subscribe( async (params) => {
            this.task_id = <number> parseInt(params['task_id'], 10);
            if(this.ready) {
                this.default_descriptor.context.domain = ["id", "=", this.task_id];
                this.default_descriptor.context.reset = true;
                this.context.change(this.default_descriptor);
            }
        });
    }
}
