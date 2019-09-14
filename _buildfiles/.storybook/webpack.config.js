const path = require('path')

module.exports = async ({ config, mode }) => {
        config.module.rules.push(     {
            test: /\.tsx?$/, // typescript loader for the .ts and .tsx files
            use: [
                { loader: 'babel-loader' },
                {
                    loader: 'ts-loader',
                    options: {
                        appendTsSuffixTo: [/\.vue$/], // needed to import vue's template files in .ts files
                        configFile: path.resolve(
                            // path to the tsconfig.json file
                            __dirname,
                            '../tsconfig.json'
                        )
                    }
                }
            ],

            exclude: /node_modules/
        })
   
        config.module.rules.push({
            test: /\.less$/,
            use: [
                {
                    loader: 'style-loader'
                },
                {
                    loader: 'css-loader'
                },
                {
                    loader: 'less-loader'
                }
            ]
        })
        config.resolve.modules.push(path.resolve(__dirname, '../node_modules')) 
        config.resolve.extensions.push('.ts', '.tsx' , ".vue");
        config.resolve.alias.vue$ = 'vue/dist/vue.esm.js'
        config.resolve.alias.core = path.resolve(__dirname, '../../')
        config.resolve.alias.core_agp = path.resolve(__dirname, '../../../core_agp')
        config.resolve.alias.core_customer = path.resolve(__dirname, '../../../core_customer')
    return config
    
}
