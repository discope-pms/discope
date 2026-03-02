# eQual STD App

The STD App is a versatile application designed to serve as a surrogate for other apps. It operates in tandem with packages that outline an app's structure in their manifest.json file.
Users can customize the STD App by providing params such as menu placement, app name, and default context.
By leveraging Material Design and Angular UI contexts, STD App offers a streamlined way to utilize these design principles and functionalities without the need for coding specific routes and behaviors.


## Build the app

Run `./serve.sh` for a dev server. Navigate to `http://equal.local:4200/`. The app will automatically reload if you change any of the source files.

Running `./build.sh` produces a `web.app` archive holding the full transpiled version of the App (ES2015), along with an addition `version`  file holding the MD5 signature of the archive.

Running `./export.sh` will export the `manifest.json`, `version`, and `web.app` files to the parent folder (which is expected to be the `app` folder located under `packages/core/apps`).


## Dependencies
The app relies on equalUI shared-lib and the equal bundle. Those are expected to be found under `node_modules`.
Note : Shared Lib must be built and then exported using `npm link sb-shared-lib`.


## Front-End Coding Conventions (JS/TS)

### Class Members
- Properties before methods
- The first method is always the constructor
- When relevant, properties are injected via the constructor
- Explicit access modifiers (visibility): `public`, `protected`, `private`
- Explicit instruction terminators (`;`)

### Syntax

(See Contribution Guide)[https://doc.equal.run/contributing/contribution-guide.md]

### Shared Library

The following services and components are integrated into SharedLib and should be used when applicable:

- Angular Material components
- ApiService
- Avoid using composite types: either scalar or standard types, or define specific types (custom).

### Errors Feedback

Whether for errors or actions that imply a potential change on the server side:

- Displayed with a snack at the bottom left of the screen for 3 seconds
- Error handling
- Use of try{} catch blocks
