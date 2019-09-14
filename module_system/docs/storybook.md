# Storybook
Storybook is a nice way to display the Vue components , interact with them and learn which props they need and which events the emit.
## Browse Components (AKA Stories)
To browse the stories , please cd to core/_buildfiles and run 
```
npm run storybook
```
This will launch the storybook GUI where all the registered stories are displayed.

## Write Stories
In order to Write stories , create a file at the same directory where your component is. Please make sure to name your story : MyNiceComponent<b>.story.ts</b>
. This is very important in order to avoid building this files in the production bundle.
## Add Addons
To add storybook addons register them at core/_buildfiles/.storybook/addons.js
## Change config
To change the config please edit the config.js found at core/_buildfiles/.storybook/config.js
## Change webpack.config for Storybook
The webpack config for storybook is found at core/_buildfiles/webpack.config.js