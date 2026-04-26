(function (wp) {
  var wpAmeliaLabels = 'wpAmeliaLabels' in window ? window.wpAmeliaLabels : {data: {}}

  var el = wp.element.createElement
  var components = wp.components
  var blockControls = wp.editor.BlockControls
  var inspectorControls = wp.editor.InspectorControls
  var data = wpAmeliaLabels.data

  var blockStyle = {
    color: 'red'
  }

  var entityNames = ['events', 'tags', 'locations']
  var entities = {}

  entityNames.forEach((entityName) => {
    entities[entityName] = []
    if (data[entityName].length !== 0) {
      for (let i = 0; i < data[entityName].length; i++) {
        entities[entityName].push({
          value: data[entityName][i].id,
          text: data[entityName][i].name + (entityName !== 'tags'
            ? ' (id: ' + data[entityName][i].id + ')' + (data[entityName][i].formattedPeriodStart ? (' - ' + data[entityName][i].formattedPeriodStart) : '')
            : '')
        })
      }
    }
  })

  // Registering the Block for events shortcode
  wp.blocks.registerBlockType('amelia/events-list-booking-gutenberg-block', {
    title: wpAmeliaLabels.events_list_booking_gutenberg_block.title,
    description: wpAmeliaLabels.events_list_booking_gutenberg_block.description,
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
      'events'
    ],
    supports: {
      customClassName: false,
      html: false
    },
    attributes: {
      short_code: {
        type: 'string',
        default: '[ameliaeventslistbooking]'
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
      event: {
        type: 'array',
        default: []
      },
      recurring: {
        type: 'boolean',
        default: false
      },
      tag: {
        type: 'array',
        default: []
      },
      location: {
        type: 'array',
        default: []
      },
      eventOptions: {
        type: 'string',
        default: ''
      },
      parametars: {
        type: 'boolean',
        default: false
      }
    },
    edit: function (props) {
      var inspectorElements = []
      var attributes = props.attributes

      var options = {
        events: [{value: '', label: wpAmeliaLabels.show_all_events}],
        tags: [{value: '', label: wpAmeliaLabels.show_all_tags}],
        locations: [{value: '', label: wpAmeliaLabels.show_all_locations}],
        eventOptions: [
          {value: 'events', label: wpAmeliaLabels.show_event},
          {value: 'tags', label: wpAmeliaLabels.show_tag}
        ],
        trigger_type: [
          {value: 'id', label: wpAmeliaLabels.trigger_type_id},
          {value: 'class', label: wpAmeliaLabels.trigger_type_class}
        ]
      }

      function getOptions (data) {
        var options = []
        data = Object.keys(data).map(function (key) {
          return data[key]
        })

        data.sort(function (a, b) {
          if (parseInt(a.value) < parseInt(b.value)) {
            return -1
          }

          if (parseInt(a.value) > parseInt(b.value)) {
            return 1
          }

          return 0
        })

        data.forEach(function (element) {
          options.push({value: element.value, label: element.text})
        })

        return options
      }

      Object.keys(entities).forEach(entity => {
        getOptions(entities[entity])
          .forEach(function (element) {
            options[entity].push(element)
          })
      })

      function getShortCode (props, attributes) {
        let shortCodeString = ''
        let shortCode = ''

        if (entities.events.length !== 0) {
          if (attributes.event !== '' && attributes.event.length && attributes.event[0] !== '') {
            shortCodeString += ' event=' + attributes.event + ''

            if (attributes.recurring) {
              shortCodeString += ' recurring=1'
            }
          }

          if (entities.tags.length !== 0) {
            if (attributes.tag && attributes.tag.length && attributes.tag[0] !== '') {
              shortCodeString += ' tag="'
              attributes.tag.forEach((tag, index) => {
                if (tag) {
                  shortCodeString += (index === 0 ? '' : ',') + '{' + tag + '}'
                }
              })
              shortCodeString += '"'
            }
          }

          if (entities.locations.length !== 0) {
            if (attributes.location && attributes.location.length && attributes.location[0] !== '') {
              shortCodeString += ' location=' + attributes.location + ''
            }
          }

          shortCode += '[ameliaeventslistbooking' + shortCodeString

          if (attributes.trigger) {
            shortCode += ' trigger=' + attributes.trigger + ''
          }

          if (attributes.trigger && attributes.trigger_type) {
            shortCode += ' trigger_type=' + attributes.trigger_type + ''
          }

          if (attributes.trigger && attributes.in_dialog) {
            shortCode += ' in_dialog=1'
          }

          shortCode += ']'
        } else {
          shortCode = 'Notice: Please create event first.'
        }

        props.setAttributes({short_code: shortCode})

        return shortCode
      }

      if (entities.events.length !== 0) {
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
          inspectorElements.push(el('div', {class: 'amelia-gutenberg-multi-select-note'}, wpAmeliaLabels.multiselect_note))

          if (entities.tags.length) {
            inspectorElements.push(el(components.SelectControl, {
              id: 'amelia-js-select-tag',
              className: 'amelia-gutenberg-multi-select',
              label: wpAmeliaLabels.select_tag,
              value: attributes.tag,
              options: options.tags,
              multiple: true,
              onChange: function (selectControl) {
                return props.setAttributes({tag: selectControl})
              }
            }))
          }

          if (entities.events.length) {
            inspectorElements.push(el(components.SelectControl, {
              id: 'amelia-js-select-event',
              className: 'amelia-gutenberg-multi-select',
              label: wpAmeliaLabels.select_event,
              value: attributes.event,
              options: options.events,
              multiple: true,
              onChange: function (selectControl) {
                return props.setAttributes({event: selectControl})
              }
            }))

            inspectorElements.push(el('div', {style: {'margin-bottom': '1em'}}, ''))

            inspectorElements.push(el(components.PanelRow,
              {},
              el('label', {htmlFor: 'amelia-js-today'}, wpAmeliaLabels.recurring_event),
              el(components.FormToggle, {
                id: 'amelia-js-recurring',
                checked: attributes.recurring,
                onChange: function () {
                  return props.setAttributes({recurring: !props.attributes.recurring})
                }
              })
            ))
          }

          if (entities.locations.length) {
            inspectorElements.push(el(components.SelectControl, {
              id: 'amelia-js-select-location',
              className: 'amelia-gutenberg-multi-select',
              label: wpAmeliaLabels.select_location,
              value: attributes.location,
              options: options.locations,
              multiple: true,
              onChange: function (selectControl) {
                return props.setAttributes({location: selectControl})
              }
            }))
          }

          inspectorElements.push(el('div', {style: {'margin-bottom': '1em'}}, ''))

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
        } else {
          attributes.event = ''
          attributes.tag = ''
        }

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
        inspectorElements.push(el('p', {style: {'margin-bottom': '1em'}}, 'Please create event first. You can find instructions in our documentation on link below.'))
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
