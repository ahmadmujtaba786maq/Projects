(function (wp) {
  var el = wp.element.createElement
  var components = wp.components
  var blockControls = wp.editor.BlockControls
  var inspectorControls = wp.editor.InspectorControls
  var data = wpAmeliaLabels.data

  var categories = []
  var services = []
  var employees = []
  var locations = []
  var packages = []

  var blockStyle = {
    color: 'red'
  }

  if (data.categories.length !== 0) {
    for (let i = 0; i < data.categories.length; i++) {
      categories.push({
        value: data.categories[i].id,
        text: data.categories[i].name + ' (id: ' + data.categories[i].id + ')'
      })
    }
  } else {
    categories = []
  }

  if (data.servicesList.length !== 0) {
    // Create array of services objects
    for (let i = 0; i < data.servicesList.length; i++) {
      if (data.servicesList[i].length !== 0) {
        services.push({
          value: data.servicesList[i].id,
          text: data.servicesList[i].name + ' (id: ' + data.servicesList[i].id + ')'
        })
      }
    }
  } else {
    services = []
  }

  if (data.employees.length !== 0) {
    // Create array of employees objects
    for (let i = 0; i < data.employees.length; i++) {
      employees.push({
        value: data.employees[i].id,
        text: data.employees[i].firstName + ' ' + data.employees[i].lastName + ' (id: ' + data.employees[i].id + ')'
      })
    }
  } else {
    employees = []
  }

  if (data.locations.length !== 0) {
    // Create array of locations objects
    for (let i = 0; i < data.locations.length; i++) {
      locations.push({
        value: data.locations[i].id,
        text: data.locations[i].name + ' (id: ' + data.locations[i].id + ')'
      })
    }
  } else {
    locations = []
  }

  if (data.packages.length !== 0) {
    // Create array of packages objects
    for (let i = 0; i < data.packages.length; i++) {
      packages.push({
        value: data.packages[i].id,
        text: data.packages[i].name + ' (id: ' + data.packages[i].id + ')'
      })
    }
  } else {
    packages = []
  }

  // Registering the Block for booking shotcode
  wp.blocks.registerBlockType('amelia/catalog-booking-gutenberg-block', {
    title: wpAmeliaLabels.catalog_booking_gutenberg_block.title,
    description: wpAmeliaLabels.catalog_booking_gutenberg_block.description,
    icon: el('svg', {width: '25', height: '27', viewBox: '0 0 25 27'},
      el('path', {
        style: {fill: '#1A84EE'},
        d: 'M11.4937358,10.7089033 L11.4937358,2.00347742 C11.4937358,0.463573647 9.83438046,-0.498899048 8.50694847,0.271096622 L0.995613218,4.6279253 C0.379532759,4.98520749 1.74329502e-05,5.64565414 1.74329502e-05,6.36030609 L1.74329502e-05,15.0243117 C1.74329502e-05,16.5606252 1.65222529,17.5235357 2.97965728,16.7608958 L10.4910797,12.4454874 C11.1110826,12.0891685 11.4937358,11.4265326 11.4937358,10.7089033'
      }),
      el('path', {
        style: {fill: '#005AEE'},
        d: 'M13.4849535,2.00346866 L13.4849535,10.7088945 C13.4849535,11.4265238 13.8676068,12.0891597 14.4876097,12.4453911 L21.9991193,16.7608871 C23.3265512,17.5235269 24.9787591,16.5606164 24.9787591,15.024303 L24.9787591,6.36029734 C24.9787591,5.64564538 24.5992438,4.98519874 23.9831633,4.62791654 L16.4717409,0.271000296 C15.1443089,-0.498907805 13.4849535,0.46356489 13.4849535,2.00346866'
      }),
      el('path', {
        style: {fill: '#3BA6FF'},
        transform: 'translate(2.876437, 13.843371)',
        d: 'M8.62445527,0.32630898 L1.0701478,4.66641195 C-0.263647222,5.43264214 -0.267569636,7.36354223 1.06300029,8.13537686 L8.61730776,12.5170752 C9.23338822,12.8744449 9.99241887,12.8744449 10.6084993,12.5170752 L18.162894,8.13537686 C19.4934639,7.36354223 19.4895415,5.43264214 18.1557465,4.66641195 L10.601439,0.32630898 C9.98893228,-0.0256314947 9.23687481,-0.0256314947 8.62445527,0.32630898'
      })
    ),
    category: 'amelia-blocks',
    keywords: [
      'amelia',
      'catalog'
    ],
    supports: {
      customClassName: false,
      html: false
    },
    attributes: {
      short_code: {
        type: 'string',
        default: '[ameliacatalogbooking]'
      },
      trigger: {
        type: 'string',
        default: ''
      },
      trigger_type: {
        type: 'string',
        default: 'id'
      },
      in_dialog: {
        type: 'boolean',
        default: false
      },
      show: {
        type: 'string',
        default: ''
      },
      location: {
        type: 'array',
        default: []
      },
      package: {
        type: 'array',
        default: []
      },
      category: {
        type: 'array',
        default: []
      },
      categoryOptions: {
        type: 'string',
        default: ''
      },
      service: {
        type: 'array',
        default: []
      },
      employee: {
        type: 'array',
        default: []
      },
      parametars: {
        type: 'boolean',
        default: false
      },
      skip_categories: {
        type: 'boolean',
        default: false
      }
    },
    edit: function (props) {
      var inspectorElements = []
      var attributes = props.attributes
      var options = []

      options['categoryOptions'] = [
        {value: '', label: wpAmeliaLabels.show_catalog},
        {value: 'categories', label: wpAmeliaLabels.show_categories},
        {value: 'services', label: wpAmeliaLabels.show_services}
      ]

      if (packages.length) {
        options['categoryOptions'].push({value: 'packages', label: wpAmeliaLabels.show_packages})
      }

      options['categories'] = []
      options['services'] = []
      options['packages'] = []
      options['employees'] = [{value: '', label: wpAmeliaLabels.show_all_employees}]
      options['locations'] = [{value: '', label: wpAmeliaLabels.show_all_locations}]
      options['show'] = [{value: '', label: wpAmeliaLabels.show_all}, {value: 'services', label: wpAmeliaLabels.services}, {value: 'packages', label: wpAmeliaLabels.packages}]
      options['trigger_type'] = [{value: 'id', label: wpAmeliaLabels.trigger_type_id}, {value: 'class', label: wpAmeliaLabels.trigger_type_class}]

      function getOptions (data) {
        var options = []

        data = Object.keys(data).map(function (key) {
          return data[key]
        })

        data.sort(function (a, b) {
          if (parseInt(a.value) < parseInt(b.value)) return -1
          if (parseInt(a.value) > parseInt(b.value)) return 1
          return 0
        })

        data.forEach(function (element) {
          options.push({value: element.value, label: element.text})
        })

        return options
      }

      getOptions(packages)
        .forEach(function (element) {
          options['packages'].push(element)
        })

      getOptions(categories)
        .forEach(function (element) {
          options['categories'].push(element)
        })

      getOptions(services)
        .forEach(function (element) {
          options['services'].push(element)
        })

      getOptions(employees)
        .forEach(function (element) {
          options['employees'].push(element)
        })

      if (locations.length) {
        getOptions(locations)
          .forEach(function (element) {
            options['locations'].push(element)
          })
      }

      function getShortCode (props, attributes) {
        let shortCodeString = ''
        let shortCode = ''

        if (categories.length !== 0 && services.length !== 0 && employees.length !== 0) {
          if (attributes.employee && attributes.employee.length && !attributes.employee.includes('')) {
            shortCodeString += ' employee=' + attributes.employee + ''
          }

          if (attributes.location && attributes.location.length && !attributes.location.includes('')) {
            shortCodeString += ' location=' + attributes.location + ''
          }

          if (attributes.categoryOptions === 'categories') {
            if (attributes.show) {
              shortCodeString += ' show=' + attributes.show
            }
            let category = attributes.category && attributes.category.length > 0 ? 'category=' + attributes.category : ''
            shortCode += '[ameliacatalogbooking ' + category + shortCodeString
          } else if (attributes.categoryOptions === 'services') {
            if (attributes.show && attributes.show !== 'packages') {
              shortCodeString += ' show=' + attributes.show
            }
            let service = attributes.service && attributes.service.length > 0 ? 'service=' + attributes.service : ''
            shortCode += '[ameliacatalogbooking ' + service + shortCodeString
          } else if (attributes.categoryOptions === 'packages') {
            let packages = attributes.package && attributes.package.length > 0 ? 'package=' + attributes.package : ''
            shortCode += '[ameliacatalogbooking ' + packages + shortCodeString
          } else {
            if (attributes.show) {
              shortCodeString += ' show=' + attributes.show
            }
            shortCode += '[ameliacatalogbooking' + shortCodeString
          }

          if (attributes.trigger) {
            shortCode += ' trigger=' + attributes.trigger + ''
          }

          if (attributes.trigger && attributes.trigger_type) {
            shortCode += ' trigger_type=' + attributes.trigger_type + ''
          }

          if (attributes.trigger && attributes.in_dialog) {
            shortCode += ' in_dialog=1'
          }

          if (attributes.skip_categories) {
            shortCode += ' categories_hidden=1'
          }

          shortCode += ']'
        } else {
          shortCode = 'Notice: Please create category, service and employee first.'
        }

        props.setAttributes({short_code: shortCode})

        return shortCode
      }

      if (categories.length !== 0 && services.length !== 0 && employees.length !== 0) {
        inspectorElements.push(el(components.SelectControl, {
          id: 'amelia-js-select-selection-type',
          label: wpAmeliaLabels.select_catalog_view,
          value: attributes.categoryOptions,
          options: options.categoryOptions,
          onChange: function (selectControl) {
            return props.setAttributes({categoryOptions: selectControl})
          }
        }))

        if (attributes.categoryOptions === 'categories') {
          if (attributes.category === '' || attributes.category === options.categories[0].value) {
            attributes.category = [options.categories[0].value]
          }

          attributes.service = ''
          attributes.package = ''

          inspectorElements.push(el(components.SelectControl, {
            id: 'amelia-js-select-category',
            value: attributes.category,
            options: options.categories,
            multiple: true,
            className: 'amelia-gutenberg-multi-select',
            onChange: function (selectControl) {
              return props.setAttributes({category: selectControl})
            }
          }))
        } else if (attributes.categoryOptions === 'services') {
          if (attributes.service === '' || attributes.service === options.services[0].value) {
            attributes.service = [options.services[0].value]
          }

          attributes.category = ''
          attributes.package = ''

          inspectorElements.push(el(components.SelectControl, {
            id: 'amelia-js-select-service',
            value: attributes.service,
            options: options.services,
            multiple: true,
            className: 'amelia-gutenberg-multi-select',
            onChange: function (selectControl) {
              return props.setAttributes({service: selectControl})
            }
          }))
        } else if (attributes.categoryOptions === 'packages') {
          if (attributes.package === '' || attributes.package === options.packages[0].value) {
            attributes.package = [options.packages[0].value]
          }
          attributes.category = ''
          attributes.service = ''

          inspectorElements.push(el(components.SelectControl, {
            id: 'amelia-js-select-package',
            value: attributes.package,
            options: options.packages,
            multiple: true,
            className: 'amelia-gutenberg-multi-select',
            onChange: function (selectControl) {
              return props.setAttributes({package: selectControl})
            }
          }))
        }

        inspectorElements.push(el('div', {style: {'margin-bottom': '1em'}}, ''))

        inspectorElements.push(el(components.PanelRow,
          {},
          el('label', {htmlFor: 'amelia-js-parametars'}, wpAmeliaLabels.filter),
          el(components.FormToggle, {
            id: 'amelia-js-parametars',
            checked: attributes.parametars,
            onChange: function () {
              return props.setAttributes({parametars: !props.attributes.parametars})
            }
          })
        ))

        inspectorElements.push(el('div', {style: {'margin-bottom': '1em'}}, ''))

        if (attributes.parametars) {
          inspectorElements.push(el(components.PanelRow,
            {},
            el('label', {htmlFor: 'amelia-js-skip-categores'}, wpAmeliaLabels.skip_categories),
            el(components.FormToggle, {
              id: 'amelia-js-skip-categores',
              checked: attributes.skip_categories,
              onChange: function () {
                return props.setAttributes({skip_categories: !props.attributes.skip_categories})
              }
            })
          ))

          inspectorElements.push(el('div', {style: {'margin-bottom': '1em'}}, ''))

          inspectorElements.push(el('div', {class: 'amelia-gutenberg-multi-select-note'}, wpAmeliaLabels.multiselect_note))

          if (employees && employees.length > 1) {
            inspectorElements.push(el(components.SelectControl, {
              id: 'amelia-js-select-employee',
              label: wpAmeliaLabels.select_employee,
              value: attributes.employee,
              options: options.employees,
              multiple: true,
              className: 'amelia-gutenberg-multi-select',
              onChange: function (selectControl) {
                return props.setAttributes({employee: selectControl})
              }
            }))

            inspectorElements.push(el('div', {style: {'margin-bottom': '1em'}}, ''))
          }

          if (locations && locations.length > 1) {
            inspectorElements.push(el(components.SelectControl, {
              id: 'amelia-js-select-location',
              label: wpAmeliaLabels.select_location,
              value: attributes.location,
              options: options.locations,
              multiple: true,
              className: 'amelia-gutenberg-multi-select',
              onChange: function (selectControl) {
                return props.setAttributes({location: selectControl})
              }
            }))

            inspectorElements.push(el('div', {style: {'margin-bottom': '1em'}}, ''))
          }

          if (options.packages.length && attributes.categoryOptions !== 'packages') {
            inspectorElements.push(el('div', {style: {'margin-bottom': '1em'}}, ''))

            inspectorElements.push(el(components.SelectControl, {
              id: 'amelia-js-select-type',
              label: wpAmeliaLabels.show_all,
              value: attributes.show,
              options: options.show,
              className: 'amelia-gutenberg-multi-select',
              onChange: function (selectControl) {
                return props.setAttributes({show: selectControl})
              }
            }))
          }
        } else {
          attributes.employee = ''
          attributes.location = ''
          attributes.skip_categories = false
        }

        inspectorElements.push(el(components.TextControl, {
          id: 'amelia-js-trigger',
          label: wpAmeliaLabels.manually_loading,
          value: attributes.trigger,
          help: wpAmeliaLabels.manually_loading_description,
          onChange: function (TextControl) {
            return props.setAttributes({trigger: TextControl})
          }
        }))

        inspectorElements.push(el(components.SelectControl, {
          id: 'amelia-js-trigger_type',
          label: wpAmeliaLabels.trigger_type,
          value: attributes.trigger_type,
          options: options.trigger_type,
          help: wpAmeliaLabels.trigger_type_tooltip,
          onChange: function (selectControl) {
            return props.setAttributes({trigger_type: selectControl})
          }
        }))

        inspectorElements.push(el(components.PanelRow,
          {},
          el('label', {htmlFor: 'amelia-js-in-dialog'}, wpAmeliaLabels.in_dialog),
          el(components.FormToggle, {
            id: 'amelia-js-in-dialog',
            checked: attributes.in_dialog,
            onChange: function () {
              return props.setAttributes({in_dialog: !props.attributes.in_dialog})
            }
          })
        ))

        return [
          el(blockControls, {key: 'controls'}),
          el(inspectorControls, {key: 'inspector'},
            el(components.PanelBody, {initialOpen: true},
              inspectorElements
            )
          ),
          el('div', {},
            getShortCode(props, props.attributes)
          )
        ]
      } else {
        inspectorElements.push(el('p', {style: {'margin-bottom': '1em'}}, 'Please create category, services and employee first. You can find instructions in our documentation on link below.'))
        inspectorElements.push(el('a', {href: 'https://wpamelia.com/quickstart/', target: '_blank', style: {'margin-bottom': '1em'}}, 'Start working with Amelia WordPress Appointment Booking plugin'))

        return [
          el(blockControls, {key: 'controls'}),
          el(inspectorControls, {key: 'inspector'},
            el(components.PanelBody, {initialOpen: true},
              inspectorElements
            )
          ),
          el('div',
            {style: blockStyle},
            getShortCode(props, props.attributes)
          )
        ]
      }
    },

    save: function (props) {
      return (
        el('div', {},
          props.attributes.short_code
        )
      )
    }
  })
})(
  window.wp
)
