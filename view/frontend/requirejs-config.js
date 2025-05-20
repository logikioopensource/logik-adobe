var config = {
    paths: {
        'logik-configurator': 'Logik_Integration/js/logik-configurator'
    },
    shim: {
        'logik-configurator': {
            deps: ['jquery', 'ko']
        }
    },
    map: {
        '*': {
            'Logik_Integration/js/content-type/logik-configurator': 'Logik_Integration/js/content-type/logik-configurator',
            'Logik_Integration/js/content-type/logik-configurator/preview': 'Logik_Integration/js/content-type/logik-configurator/preview'
        }
    }
}; 