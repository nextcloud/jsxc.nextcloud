/* global module:false */
module.exports = function(grunt) {

   var app = grunt.file.readJSON('package.json');

   // Project configuration.
   grunt.initConfig({
      app: app,
      version: grunt.option('ver') || app.version,
      meta: {
         banner: grunt.file.read('js/jsxc/banner.js')
      },
      jshint: {
         options: {
            jshintrc: '.jshintrc'
         },
         gruntfile: {
            src: 'Gruntfile.js'
         },
         files: [ 'js/ojsxc.js' ]
      },
      copy: {
         build: {
            files: [ {
               expand: true,
               src: [ 'js/*.js', 'js/settings/**', 'css/*', 'appinfo/*', 'ajax/*', 'img/**', 'templates/**', 'sound/*', 'lib/**', 'settings/**', 'LICENSE' ],
               dest: 'build/'
            }, {
               expand: true,
               cwd: 'js/jsxc/build/',
               src: [ '**' ],
               dest: 'build/js/jsxc/'
            } ]
         },
         css: {
            files: [ {
               expand: true,
               cwd: 'js/jsxc/lib/',
               src: ['*.css'],
               dest: 'css/'
            } ]
         }
      },
      clean: [ 'build/' ],
      usebanner: {
         dist: {
            options: {
               position: 'top',
               banner: '<%= meta.banner %>'
            },
            files: {
               src: [ 'build/js/*.js', 'build/css/jsxc.oc.css' ]
            }
         }
      },
      replace: {
         info: {
            src: [ 'build/appinfo/info.xml', 'appinfo/info.xml' ],
            overwrite: true,
            replacements: [ {
               from: /<version>[^<]+<\/version>/,
               to: "<version><%= version %></version>"
            } ]
         },
         version: {
            src: [ 'build/appinfo/version', 'appinfo/version' ],
            overwrite: true,
            replacements: [ {
               from: /.+/,
               to: "<%= version %>"
            } ]
         },
         imageUrl: {
            src: ['css/*.css'],
            overwrite: true,
            replacements: [
               {
                  from: /image-url\(["'](.+)["']\)/g,
                  to: 'url(\'../js/jsxc/img/$1\')'
               }
            ]
         }
      },
      search: {
         console: {
            files: {
               src: [ 'js/*.js' ]
            },
            options: {
               searchString: /console\.log\((?!'[<>]|msg)/g,
               logFormat: 'console',
               failOnMatch: true
            }
         },
         changelog: {
            files: {
               src: [ 'CHANGELOG.md' ]
            },
            options: {
               searchString: "## <%= version %>",
               logFormat: 'console',
               onComplete: function(m) {
                  if (m.numMatches === 0) {
                     grunt.fail.fatal("No entry in CHANGELOG.md for current version found.");
                  }
               }
            }
         },
         changelogNightly: {
            files: {
               src: [ 'CHANGELOG.md' ]
            },
            options: {
               searchString: "^## [Unreleased]",
               logFormat: 'console',
               onComplete: function(m) {
                  if (m.numMatches === 0) {
                     grunt.fail.fatal("No entry in CHANGELOG.md for nightly found.");
                  }
               }
            }
         }
      },
      compress: {
         main: {
            options: {
               archive: 'archives/ojsxc-<%= version %>.tar.gz',
               mode: 'tgz'
            },
            files: [ {
               src: [ '**' ],
               expand: true,
               dest: 'ojsxc/',
               cwd: 'build/'
            } ]
         }
      },
      exec: {
        signRelease: {
          command: 'openssl dgst -sha512 -sign ' +
            '~/.nextcloud/certificates/ojsxc.key ' +
            'archives/ojsxc-<%= version %>.tar.gz | openssl base64 > ' +
            'archives/ojsxc-<%= version %>.tar.gz.sig'
        }
      },
      autoprefixer: {
         no_dest: {
             src: 'css/*.css'
         }
      },
      sass: {
         dist: {
             files: {
                'css/jsxc.oc.css': 'scss/jsxc.oc.scss'
             }
         }
       },
       dataUri: {
          dist: {
            src: 'css/jsxc.oc.css',
            dest: 'build/css/',
            options: {
              target: ['img/*.*', 'js/jsxc/img/*.*', 'js/jsxc/img/**/*.*'],
              /*fixDirLevel: true,
              baseDir: './',*/
              maxBytes: 1 //2048
            }
          }
        },
        watch: {
            css: {
                files: ['js/jsxc/scss/*', 'scss/*'],
                tasks: ['sass', 'autoprefixer', 'replace:imageUrl']
            }
        }
   });

   // These plugins provide necessary tasks.
   grunt.loadNpmTasks('grunt-contrib-jshint');
   grunt.loadNpmTasks('grunt-contrib-copy');
   grunt.loadNpmTasks('grunt-contrib-clean');
   grunt.loadNpmTasks('grunt-banner');
   grunt.loadNpmTasks('grunt-text-replace');
   grunt.loadNpmTasks('grunt-search');
   grunt.loadNpmTasks('grunt-contrib-compress');
   grunt.loadNpmTasks('grunt-sass');
   grunt.loadNpmTasks('grunt-autoprefixer');
   grunt.loadNpmTasks('grunt-data-uri');
   grunt.loadNpmTasks('grunt-contrib-watch');
   grunt.loadNpmTasks('grunt-exec');

   grunt.registerTask('default', [ 'build', 'watch' ]);

   grunt.registerTask('build', ['copy:css', 'sass', 'replace:imageUrl', 'autoprefixer']);

   grunt.registerTask('build:prerelease', [ 'jshint', 'search:console', 'clean', 'build', 'copy:build', 'dataUri', 'usebanner', 'replace', 'compress' ]);

   grunt.registerTask('build:release', [ 'search:changelog', 'build:prerelease' ]);

   grunt.registerTask('sign:release', [ 'exec:signRelease' ]);
};
