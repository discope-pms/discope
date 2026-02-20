import { NgModule } from '@angular/core';
import { TranslatePipe } from './translate.pipe';
import { TranslateWithVarPipe } from './translate-with-var.pipe';

const sharedPipes = [
    TranslatePipe, TranslateWithVarPipe
];

@NgModule({
    imports: [],
    declarations: [
        ...sharedPipes
    ],
    providers: [],
    exports: [
        ...sharedPipes
    ]
})
export class AppSharedPipesModule { }
