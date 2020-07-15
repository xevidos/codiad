( function( global, $ ) {
	
	// Define core
	let codiad = global.codiad,
	scripts = document.getElementsByTagName( 'script' ),
	path = scripts[scripts.length-1].src.split( '?' )[0],
	curpath = path.split( '/' ).slice( 0, -1 ).join( '/' ) + '/';
	
	codiad.forms = function( instance = null ) {
		
		let _i = this;
		
		this.back_label = "Back";
		this.next_label = "Next";
		this.page = 0;
		this.saving = false;
		this.submit_label = "Submit";
		this.topics = {};
		
		
		//Sub objects
		this.m = {
			
			conditional: function( o = {} ) {
				
				let self = this;
				this.form = _i;
				this.parent = null;
				this.subject = null;
				this.valid_actions = [
					"change_type",
					"hide",
					"show",
				];
				this.values = [];
				
				this.change_type = function() {
					
					let p = self.parent;
					
					if( p.type === self.type ) {
						
						return;
					}
					
					if( p.element ) {
						
						let c = p.element.parent().parent();
						let element = p.element;
						
						element.detach();
						element.off();
						
						if( c.length ) {
							
							c.remove();
						}
						p.element = null;
					}
					
					p.type = self.type;
					
					let a = self.form.m.create( p );
					
					self.parent.replace( a );
					
					if( self.parent.previous.element.parent().length ) {
						
						self.form.v.add( self.parent, null, self.parent.previous.element, null, true, false );
					} else if( c.element.parent().length ) {
						
						self.form.v.add( self.parent, c.element, null, null, true, false );
					} else {
						
						self.form.v.add( self.parent, null, null, null, true, false );
					}
				};
				this.check = function() {
					
					let value = null;
					let total = self.values.length;
					let pass = false;
					
					if( typeof self.subject === "object" ) {
						
						value = self.subject.value;
					} else if( typeof self.subject === "function" ) {
						
						value = self.subject();
					}
					
					for( let i = 0;i < total;i++ ) {
						
						if( self.values[i].type ) {
							
							self.type = self.values[i].type;
						} else if( self.values[i].action === "change_type" ) {
							
							self.type = self.parent.type;
						}
						
						if( value === self.values[i].value || `${value}` === `${self.values[i].value}` ) {
							
							pass = self.values[i];
							break;
						} else {
							
							pass = false;
						}
					}
					return pass;
				};
				this.hide = function() {
					
					self.form.v.remove( this.parent );
				};
				this.init = function( o ) {
					
					$.each( o, function( key, value ) {
						
						if( typeof o[key] === "object" && o[key] !== null && ! Array.isArray( o[key] ) ) {
							
							self[key] = $.extend( true, self[key], value );
						} else {
							
							self[key] = value;
						}
					});
				};
				this.show = function() {
					
					let i = true;
					let j = false;
					
					while( i === true ) {
						
						if( j === false ) {
							
							j = self.parent.previous;
						} else {
							
							j = j.previous;
						}
						
						if( j === null ) {
							
							i = false;
							self.form.v.add( self.parent, null, null, null, true );
							break;
						}
						
						if( j.element.parent().length ) {
							
							i = false;
							self.form.v.add( self.parent, j.element.parent(), null, null, true );
							break;
						} else if( j.parent.element && j.parent.element.parent().length ) {
							
							i = false;
							self.form.v.add( self.parent, j.parent.element.parent(), null, null, true );
							break;
						}
					}
				};
				
				self.init( o );
			},
			data: {},
			modal_data: function() {
				
				let self = this;
				this.class_names = {};
				this.conditions = {};
				this.css = {};
				this.default = "";
				this.description = null;
				this.element = null;
				this.form = _i;
				this.key = null;
				this.label = "";
				this.listeners = {
					
					change: [],
					update_modal: [],
					update_view: [],
				};
				this.name = "";
				this.next = null;
				this.options = {};
				this.page = 0;
				this.parent = _i.m.data;
				this.placeholder = "";
				this.previous = null;
				this.render = null;
				this.required = false;
				this.shown_by_default = true;
				this.subdescription = null;
				this.subfields = {};
				this.subscriptions = [];
				this.subtitle = null;
				this.title = null;
				this.type = "text";
				this.update = null;
				this.validation = null;
				this.value = "";
				
				this.audit = function( o ) {
					
					let r = false;
					
					if( self.type === "repeatable" ) {
						
						let length = self.value.length;
						for( let i = 0;i < length;i++ ) {
							
							self.audit( self.value[i].data );
						}
					} else {
						
						$.each( self.conditions, function( k, v ) {
							
							if( r !== false ) {
								
								return;
							}
							
							r = v.check();
							
							if( r !== false ) {
								
								if( v.valid_actions.includes( r.action ) ) {
									
									v[r.action]();
								} else if( typeof r.action === "function" ) {
									
									r.action();
								}
								return;
							}
						});
					}
				};
				this.clone = function() {
					
					return _i.m.clone( self );
				};
				this.conditionals = function() {
					
					let r = false;
					
					$.each( self.conditions, function( k, v ) {
						
						if( r !== false ) {
							
							return;
						}
						
						r = v.check();
					});
					return r;
				};
				this.publish = function() {
					
					console.log( self );
					
					let total = self.subscriptions.length;
					for( let i = 0;i < total;i++ ) {
						
						self.subscriptions[i]( self );
					}
				};
				this.remove = function() {
					
					_i.v.remove( self );
					_i.m.remove( self, _i.m.data );
				};
				this.replace = function( i, o = {} ) {
					
					let restricted = [
						"next",
						"parent",
						"previous",
					];
					
					if( typeof i === "object" ) {
						
						$.each( i, function( key, value ) {
							
							if( ! restricted.includes( key ) ) {
								
								self[key] = value;
							}
						});
					} else if( typeof i === "string" ) {
						
						self[i] = o;
					}
				};
				this.subscribe = function( subscription ) {
					
					if( typeof subscription !== "function" ) {
						
						return;
					}
					
					let index = self.subscriptions.indexOf( subscription );
					
					if( index < 0 ) {
						
						self.subscriptions.push( subscription );
					}
				};
				this.unsubscribe = function( subscription ) {
					
					let index = self.subscriptions.indexOf( subscription );
					
					if( index >= 0 ) {
						
						self.subscription.splice( index, 1 );
					}
				};
			},
			parent: _i,
			requirements: {
				
				default: [
					"default",
				],
				repeatable: [
					"default",
					"subfields",
				],
				sortable: [
					"default",
					["options", "subfields"],
				],
			},
			v: _i.v,
			
			init: function() {
				
				let _this = _i.m;
			},
			create: function( o ) {
				
				let _this = _i.m;
				
				if( typeof o !== "object" ) {
					
					throw new Error({
						message: "Creation of modal_data requires data of type Object",
						data_recieved: o,
						type_recieved: ( typeof o ),
					});
				}
				
				if( ! o.type ) {
					
					throw new Error({
						message: "Creation of modal_data requires type to be specified in the passed object",
						data_recieved: o,
						type_recieved: ( typeof o.type ),
					});
				}
				
				if( _this.requirements[`${o.type}`] ) {
					
					let keys = _this.requirements[`${o.type}`];
					let total = keys.length;
					let pass = false;
					
					for( let i = 0;i < total;i++ ) {
						
						if( typeof keys[i] === "string" ) {
							
							if( o[keys[i]] === undefined ) {
								
								reject({
									message: "Error, requirements to create object not met.",
									required_fields: o.requirements[`${o.type}`],
								});
							}
						} else if( Array.isArray( keys[i] ) ) {
							
							let inner_total = keys[i].length;
							pass = false;
							
							for( let j = 0;j < total;j++ ) {
								
								if( o[keys[i]] !== undefined ) {
									
									pass = true;
									break;
								}
							}
							
							if( ! pass ) {
								
								reject({
									message: "Error, requirements to create object not met.",
									required_fields: o.requirements[`${o.type}`],
								});
							}
						}
					}
				}
				
				let a = new _this.modal_data();
				
				a.get_value = function() {
					
					a.update();
					return a.value;
				};
				a.listeners.change = [function( o, e ) {
					
					o.update();
				}];
				
				if( o.conditions ) {
					
					a.shown_by_default = false;
				}
				
				$.each( o, function( key, value ) {
					
					a[key] = value;
				});
				a.value = a.default;
				
				switch( true ) {
					
					case( a.type === "checkbox" ):
						
						if( ! a.element ) {
							
							a.element = $( `<input type="checkbox" />` );
						}
						
						a.update = function() {
							
							a.value = !!a.element.prop( "checked" );
							a.publish();
						};
						a.render = function() {
							
							a.element.prop( "checked", a.value );
						};
					break;
					
					case( a.type === "checkboxes" ):
						
						if( ! a.element ) {
							
							a.element = $( "<div></div>" );
						}
						
						a.update = function() {
							
							let v = codiad.common.get_check_box_values( a.name );
							a.value = v;
							a.publish();
						};
						a.render = function() {
							
							let _this = a;
							let checks = a.element.find( ':checkbox' );
							
							a.element.html( '' );
							$.each( a.options, function( id, value ) {
								
								let label = $( `<label></label>` );
								let check = $( `<input type="checkbox" name="${_this.name}" value="${value}" />` );
								label.append( check );
								label.append( `${id}` );
								
								if( _this.value.includes( value ) ) {
									
									check.prop( "checked", true );
								} else {
									
									check.prop( "checked", false );
								}
								_i.v.add_listeners( _this, check );
								_this.element.append( label );
							});
						};
						a.get_value = function() {
							
							a.update();
							return a.value;
						};
					break;
					
					case( a.type === "date" ):
						
						if( ! a.element ) {
							
							a.element = $( `<input type="date" />` );
						}
					break;
					
					case( a.type === "datetime" ):
						
						if( ! a.element ) {
							
							a.element = $( `<input type="datetime-local" />` );
						}
					break;
					
					case( a.type === "email" ):
						
						if( ! o.element ) {
							
							a.element = $( `<input type="email" />` );
						}
						
						if( ! a.validation ) {
							
							a.validation = _i.validation.validate;
						}
					break;
					
					case( a.type === "hidden" ):
						
						if( ! o.element ) {
							
							a.element = $( `<input type="hidden" />` );
						}
					break;
					
					case( a.type === "number" ):
						
						if( ! a.element ) {
							
							a.element = $( `<input type="number" />` );
						}
						
						if( ! a.validation ) {
							
							a.validation = _i.validation.validate;
						}
					break;
					
					case( a.type === "phone" ):
						
						if( ! a.element ) {
							
							a.element = $( `<input type="tel" />` );
						}
						
						if( ! a.validation ) {
							
							a.validation = _i.validation.validate;
						}
					break;
					
					case( _i.m.is_repeatable( a ) ):
						
						if( ! a.element ) {
							
							if( a.type === "sortable" ) {
								
								a.element = $( `<ul></ul>` );
							} else  {
								
								a.element = $( `<div></div>` );
							}
						}
						
						a.value = [];
						
						a.add = function( o = {} ) {
							
							let _r = a;
							let subs = {};
							
							subs.data = {};
							subs.element = $( `<div class="child-container" data-type="child-container"></div>` );
							subs.type = "repeatable-data";
							
							let previous = null;
							
							$.each( _r.subfields, function( key, value ) {
								
								let object = _i.m.clone( value );
								if( o[key] !== undefined && o[key] !== null ) {
									
									object.default = o[key];
								}
								
								if( object.element ) {
									
									object.element = object.element.clone();
									object.element.detach();
									object.element.off();
								}
								
								subs.data[key] = _i.m.create( object );
								subs.data[key].parent = subs;
								subs.data[key].previous = previous;
								subs.data[key].key = key;
								
								if( previous !== null ) {
									
									previous.next = subs.data[key];
								}
								previous = subs.data[key];
							});
							
							$.each( o, function( key, value ) {
								
								if( typeof value !== "object" && Object.keys( subs ).includes( key ) ) {
									
									subs.data[key].value = value;
								} else if( typeof subs[key] === "object" && typeof value === "object" && value !== null ) {
									
									subs.data[key] = $.extend( subs.data[key], value );
								} else if( typeof value === "object" && value !== null ) {
									
									subs.data[key] = value;
								} else {
									
									console.log( "Unknown add field action", key, value );
								}
							});
							subs.parent = _r;
							subs.remove = function() {
								
								let total = subs.parent.value.length;
								
								for( let i = 0;i < total;i++ ) {
									
									if( subs.parent.value[i] === subs ) {
										
										subs.parent.value.splice( i, 1 );
									}
								}
								_r.render();
							};
							subs.render = function() {
								
								let _this = subs;
								_this.element.off();
								_this.element.detach();
								_this.element.html( "" );
								
								console.log( _this.element, _this.element[0].isConnected );
								if( ! _this.element[0].isConnected ) {
									
									_this.parent.element.append( _this.element );
								}
								
								$.each( _this.data, function( key, value ) {
									
									value.element.off();
									value.element.detach();
									value.element.html( "" );
									_i.v.add( value, _this.element );
								});
								
								let remove = $( `<a data-type="repeatable-remove">Remove</a>` );
								remove.addClass( 'button' );
								remove.on( 'click', function( e ) {
									
									_this.remove();
								});
								_this.element.append( remove );
							};
							
							_i.m.conditionals( [], subs.data );
							_r.value.push( subs );
						};
						a.get_value = function() {
							
							let _this = a;
							let data = [];
							let total = a.value.length;
							
							for( let i = 0;i < total;i++ ) {
								
								let object = {};
								$.each( a.value[i].data, function( key, value ) {
									
									object[key] = value.get_value();
								});
								data.push( object );
							}
							return data;
						};
						a.remove = function( i ) {
							
							a.value[i].remove();
							return a;
						};
						a.render = function() {
							
							a.element.html( "" );
							let total = a.value.length;
							
							for( let i = 0;i < total;i++ ) {
								
								a.value[i].render();
							}
							
							if( a.element.parent().find( '[data-type="repeatable-add"]' ).length < 1 ) {
								
								let add = $( `<a data-type="repeatable-add">${o.repeat_label}</a>` );
								add.addClass( 'button' );
								add.on( 'click', function( e ) {
									
									a.add();
									a.value[(a.value.length - 1)].render();
								});
								a.element.parent().append( add );
							}
						};
						a.update = function() {};
						
						if( a.default ) {
							
							let total = a.default.length;
							for( let i = 0;i < total;i++ ) {
								
								a.add( a.default[i] );
							}
						}
					break;
					
					case( a.type === "select" ):
						
						if( ! a.element ) {
							
							a.element = $( `<select></select>` );
						}
						
						a.render = function() {
							
							a.element.html( '' );
							let options = {};
							let _this = a;
							
							if( typeof a.options === "function" ) {
								
								options = a.options( o );
							} else {
								
								options = a.options;
							}
							
							$.each( options, function( id, value ) {
								
								let option = $( `<option value="${value}">${id}</option>` );
								_this.element.append( option );
							});
							a.element.val( a.value );
						};
					break;
					
					case( a.type === "sortable" ):
						
						if( ! a.element ) {
							
							a.element = $( `<ul></ul>` );
						}
					break;
					
					case( a.type === "weekday" ):
						
						if( ! a.element ) {
							
							a.element = $( `<select></select>` );
						}
						
						a.render = function() {
							
							a.element.html( '' );
							let options = {
								
								"Sunday": "sunday",
								"Monday": "monday",
								"Tuesday": "tuesday",
								"Wednesday": "wednesday",
								"Thursday": "thursday",
								"Friday": "friday",
								"Sunday": "sunday",
							};
							let _this = a;
							
							$.each( options, function( id, value ) {
								
								let option = $( `<option value="${value}">${id}</option>` );
								_this.element.append( option );
							});
							a.element.val( a.value );
						};
					break;
					
					case( a.type === "year" ):
						
						if( ! a.element ) {
							
							a.element = $( `<input type="text" />` );
						}
						
						if( ! a.validation ) {
							
							a.validation = _i.validation.validate;
						}
					break;
					
					default:
						
						if( ! a.element ) {
							
							a.element = $( `<input type="text" />` );
						}
					break;
				}
				
				if( typeof a.update !== "function" ) {
					
					a.update = function() {
						
						a.value = a.element.val();
						a.publish();
					}
				}
				
				if( typeof a.render !== "function" ) {
					
					a.render = function() {
						
						a.element.val( a.value );
					}
				}
				return a;
			},
			clone: function( o ) {
				
				return $.extend( true, {}, o );
			},
			conditionals: function( filter = [], data = null ) {
				
				let _this = this;
				let total_filters = filter.length;
				let root_keys = Object.keys( this.data );
				
				if( data === null ) {
					
					data = this.data;
				}
				
				let keys = Object.keys( data );
				
				$.each( data, function( key, value ) {
					
					if( total_filters > 0 && filter.inlcudes( value ) ) {
						
						return;
					}
					
					$.each( value.conditions, function( k, v ) {
						
						let c = new _this.conditional( v );
						
						c.parent = value;
						
						if( keys.includes( k ) ) {
							
							if( c.subject === null ) {
								
								c.subject = data[k];
							}
						} else {
							
							if( root_keys.includes( k ) ) {
								
								if( c.subject === null ) {
									
									c.subject = data[k];
								}
							}
						}
						c.subject.subscribe( value.audit );
						value.conditions[k] = c;
					});
				});
			},
			get_value: function( o ) {
				
				let _this = _i.m;
				let data = null;
				
				if( typeof o === "string" ) {
					
					o = _i.m.data[o];
				}
				return o.get_value();
			},
			get_values: function( o = null ) {
				
				let _this = _i.m;
				let data = {};
				
				if( o === null ) {
					
					o = this.data;
				}
				
				$.each( o, function( key, value ) {
					
					data[key] = _this.get_value( value );
				});
				return data;
			},
			is_repeatable: function( o ) {
				
				return ( o.type === "repeatable" || ( o.type === "sortable" && Object.keys( o.subfields ).length ) );
			},
			merge: function( ...objects ) {
				
				let o = objects[0];
				let total = objects.length;
				
				for( let i = 0;i < total;i++ ) {
					
					$.extend( true, o, objects[i] );
				}
				return o;
			},
			remove: function( o, data ) {
				
				$.each( data, function( key, value ) {
					
					if( value === o ) {
						
						_i.v.remove( value );
						data[key] = undefined;
						delete data[key];
					}
				});
			},
			set_data: function() {},
			update: function() {},
		};
		this.publish = function( topic, info ) {
			
			let _this = _i;
			
			// If the topic doesn't exist, or there's no listeners in queue, just leave
			if( ! _this.topics.hasOwnProperty.call( _this.topics, topic ) ) {
				
				return;
			}
			
			// Cycle through topics queue, fire!
			_this.topics[topic].forEach( function( item ) {
				
				item( info !== undefined ? info : {} );
			});
		};
		this.subscribe = function( topic, listener ) {
			
			let _this = _i;
			let add = true;
			
			// Create the topic's object if not yet created
			if( ! _this.topics.hasOwnProperty.call( _this.topics, topic ) ) {
				
				_this.topics[topic] = [];
			}
			
			// Add the listener to queue if not already in it.
			for( let i = _this.topics[topic].length;i--; ) {
				
				if( _this.topics[topic][i] == listener ) {
					
					add = false;
					break;
				}
			}
			
			if( add ) {
				
				let index = _this.topics[topic].push( listener ) - 1;
			}
			
			// Provide handle back for removal of topic
			return {
				remove: function() {
					
					delete _this.topics[topic][index];
				}
			};
		};
		this.v = {
			
			checking_conditions: false,
			container: null,
			controls: null,
			m: _i.m,
			message: null,
			parent: _i,
			
			init: function() {
				
				let _this = this;
				
				if( _this.container === null ) {
					
					_this.container = $( "<div></div>" );
				}
				
				if( _this.message === null ) {
					
					_this.message = $( "<div></div>" );
				}
			},
			add: function( o, c = null, previous = null, next = null, show = false, conditions = true ) {
				
				let _this = _i.v;
				
				if( typeof o !== "object" ) {
					
					throw new Error({
						message: "Creation of form view object requires data of type modal_data",
						data_recieved: o,
						type_recieved: ( typeof o ),
					});
				}
				
				let connected = ( !!o.element ) ? o.element.parent().length : false;
				
				if( connected || ( ! o.shown_by_default && ! show ) ) {
					
					return;
				}
				
				if( c === null ) {
					
					c = this.container;
				}
				
				_this.add_listeners( o );
				
				let container = $( '<div class="data-container" data-type="data-container"></div>' );
				let label = $( `<label></label>` );
				let text = ( !!o.label ) ? o.label : '&nbsp;';
				let repeatable = _i.m.is_repeatable( o );
				
				let title = null;
				let description = null;
				let subtitle = null;
				let subdescription = null;
				let instructions = null;
				
				if( typeof o.title === "function" ) {
					
					title = o.title( o, container, label );
				} else if( o.title ) {
					
					if( ! $( c ).find( `#${o.key}_title` ).length ) {
						
						title = $( `<h2 id="${o.key}_title"></h2>` ).html( o.title );
					}
				}
				
				if( typeof o.description === "function" ) {
					
					description = o.description( o, container, label );
				} else if( o.description ) {
					
					if( ! $( c ).find( `#${o.key}_description` ).length ) {
						
						description = $( `<p id="${o.key}_description"></p>` ).html( o.description );
					}
				}
				
				if( typeof o.subtitle === "function" ) {
					
					subtitle = o.subtitle( o, container, label );
				} else if( o.subtitle ) {
					
					if( ! $( c ).find( `#${o.key}_subtitle` ).length ) {
						
						subtitle = $( `<h4 id="${o.key}_subtitle"></h4>` ).text( o.subtitle );
					}
				}
				
				if( typeof o.subdescription === "function" ) {
					
					subdescription = o.subdescription( o, container, label );
				} else if( o.subdescription ) {
					
					if( ! $( c ).find( `#${o.key}_subdescription` ).length ) {
						
						subdescription = $( `<br><small id="${o.key}_subdescription"></small>` ).text( o.subdescription );
					}
				}
				
				if( typeof o.label === "function" ) {
					
					text = o.label( o, container, label );
				}
				
				if( o.required === true ) {
					
					text = text + ' *';
				}
				
				label.text( text );
				
				if( o.classes && o.classes.container ) {
					
					let total_classes = o.classes.container;
					
					for( let i = 0;i < total_classes;i++ ) {
						
						o.addClass( o.classes.container[i] );
					}
				}
				
				o.element.css( o.css );
				
				if( o.title ) {
					
					container.append( title );
				}
				
				if( o.description ) {
					
					container.append( description );
				}
				
				if( o.subtitle ) {
					
					label.append( subtitle );
				}
				
				if( o.subdescription ) {
					
					label.append( subdescription );
				}
				
				if( o.placeholder ) {
					
					o.element.attr( "placeholder", o.placeholder );
				}
				
				if( o.label ) {
					
					container.append( label );
					label.append( o.element );
				} else {
					
					container.append( o.element );
				}
				
				switch( true ) {
					
					case( typeof o.load === "function" ):
						
						o.load( o );
						return o;
					break;
					
					default:
						
					break;
				}
				
				if( previous ) {
					
					container.insertAfter( previous );
				} else if( next ) {
					
					container.insertBefore( next );
				} else {
					
					c.append( container );
				}
				
				if( typeof o.render === "function" ) {
					
					o.render();
				}
				return o;
			},
			add_listeners: function( o, element = null ) {
				
				if( element === null ) {
					
					element = o.element;
				}
				
				$.each( o.listeners, function( id, value ) {
					
					if( Array.isArray( value ) ) {
						
						for( let i = value.length;i--; ) {
							
							element.on( id, function( e ) {
								
								value[i]( o, e );
							});
						}
					} else {
						
						element.on( id, function( e ) {
							
							value( o, e );
						});
					}
				});
			},
			conditionals: function( filter = [], data = null ) {
				
				let _this = this;
				let filter_length = filter.length;
				
				console.log( "checking conditions", filter, data, this.checking_conditions, _i.m.data.element );
				
				if( this.checking_conditions === true && data === null ) {
					
					return;
				}
				
				this.checking_conditions = true;
				
				if( data === null ) {
					
					data = _i.m.data;
				}
				
				$.each( data, function( key, value ) {
					
					if( filter_length && ! filter.includes( value ) ) {
						
						return;
					}
					
					if( _i.page !== value.page ) {
						
						return;
					}
					
					value.audit();
				});
				this.checking_conditions = false;
			},
			next_page: async function() {
				
				let _this = _i.v;
				let v = await _i.validation.verify();
				let total = _i.get_total_pages()
				if( _i.page < total && v ) {
					
					_i.v.render_page( _i.page + 1 );
				} else if( _i.page == total && v ) {
					
					_i.submit();
				}
				return  _i.page;
			},
			previous_page: async function() {
				
				if( _i.page > 0 ) {
					
					_i.v.render_page( _i.page - 1 );
				}
				return _i.page;
			},
			remove: function( o ) {
				
				if( typeof o !== "object" || ! o ) {
					
					throw new Error({
						message: "Removal of form view object requires data of type modal_data",
						data_recieved: o,
						type_recieved: ( typeof o.type ),
					});
				}
				
				if( ! o.element ) {
					
					console.log( "removal of blank element", o );
					return o;
				}
				
				let c = o.element.parent();
				let element = o.element;
				let i = null;
				
				i = element.detach();
				i = element.off();
				
				if( c.length ) {
					
					i = c.remove();
				}
				return o;
			},
			render_controls: function() {
				
				let _this = _i.v;
				let controls = _this.controls;
				let next = $( `<button class="button">${_i.next_label}</button>` );
				let back = $( `<button class="button">${_i.back_label}</button>` );
				
				controls.html( '' );
				
				next.on( "click", function( e ) {
					
					_this.next_page();
				});
				back.on( "click", function( e ) {
					
					_this.previous_page();
				});
				
				if( _i.page == _i.get_total_pages() ) {
					
					next.text( _i.submit_label );
					next.attr( "type", "submit" )
				}
				
				if( _i.page == 0 ) {
					
					controls.append( next );
				} else {
					
					controls.append( back );
					controls.append( next );
				}
			},
			render_page: function( id ) {
				
				let _this = this;
				
				_i.publish( "data.onWillRenderPage", {
					page: id,
					view: this,
				});
				
				_this.unrender_page( _i.page );
				_i.page = id;
				_this.container.append( _this.message );
				
				$.each( _this.m.data, function( key, value ) {
					
					if( ! value || value.page !== _i.page || value.shown_by_default === false ) {
						
						return;
					}
					
					let check = value.conditionals();
					
					console.log( check );
					
					if( check !== false && check.action === "hide" ) {
						
						return;
					}
					
					_this.add( value );
				});
				
				_this.controls = $(  `<div data-type="controls"></div>` );
				_this.container.append( _this.controls );
				_this.render_controls();
				_this.update();
				_i.publish( "data.onRenderedPage", {
					page: id,
					view: this,
				});
			},
			unrender_page: function( id ) {
				
				let _this = this;
				let keys = Object.keys( _this.m.data );
				let total = keys.length;
				
				_i.publish( "data.onUnRenderPage", {
					page: id,
					view: this,
				});
				
				for( let i = 0;i < total;i++ ) {
					
					if( _this.m.data[keys[i]].page === id ) {
						
						_this.remove( _this.m.data[keys[i]] );
					}
				}
				_this.container.html( '' );
			},
			update: function() {},
		};
		this.validation = {
			
			empty_values: [
				undefined,
				null,
				'null',
				'',
				[],
			],
			parent: _i,
			types: {
				
				email: /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/,
				number: /^(?=.)([+-]?(\d*)(\.(\d+))?)$/,
				phone: /^[(]{0,1}[0-9]{3}[)]{0,1}[-\s\.]{0,1}[0-9]{3}[-\s\.]{0,1}[0-9]{4}$/,
				year: /^[0-9]{4}$/,
			},
			
			validate: function( o ) {
				
				let _this = _i.validation;
				let pass = true;
				
				return new Promise( function( resolve, reject ) {
					
					if( Object.keys( _this.types ).includes( o.type ) ) {
						
						if( typeof _this.types[`${o.type}`] === "function" ) {
							
							_this.types[`${o.type}`]( o )
							.then( resolve );
						} else {
							
							if( o.required ) {
								
								if( _this.empty_values.includes( o.value ) ) {
									
									pass = false;
								}
							}
							
							console.log( o, o.value );
							
							if( pass && ! _this.types[`${o.type}`].test( o.value ) ) {
								
								pass = false;
							}
							
							resolve( pass );
						}
					} else {
						
						reject( false );
					}
				});
			},
			verify: function( data = null ) {
				
				return new Promise( async function( resolve, reject ) {
					
					let empty_values = [
						undefined,
						null,
						'null',
						'',
						[],
					];
					let _this = _i.validation;
					let values = [];
					
					if( data == null ) {
						
						_i.publish( "data.onWillVerify", {
							form: _i,
						});
						
						data = _i.m.data
					}
					
					$.each( data, async function( key, value ) {
						
						let show = true;
						let repeatable = _i.m.is_repeatable( value );
						let pass = true;
						
						if( value.page !== _i.page ) {
							
							return;
						}
						
						if( ! value.required && ! repeatable ) {
							
							return;
						}
						
						if( typeof value.conditions === "function" ) {
							
							show = value.conditions( value );
						} else if( value.conditions ) {
							
							$.each( value.conditions, function( i, v ) {
								
								if( ( v.value === v.o.value || `${v.value}` === `${v.o.value}` ) && show ) {
									
									show = true;
								} else {
									
									show = false;
								}
							});
						}
						
						if( ! show ) {
							
							return;
						}
						
						if( repeatable ) {
							
							let total = value.value.length;
							for( let i = 0;i < total;i++ ) {
								
								let a = await _this.verify( value.value[i].data );
								values.concat( a );
							}
						}
						
						if( value.subfields ) {
							
							if( value.required && ! value.value.length ) {
								
								pass = false;
							}
						}
						
						if( typeof value.validation === "function" ) {
							
							pass = await value.validation( value );
						}
						
						values.push( pass );
						if( pass ) {
							
							value.element.parent().css( 'color', 'black' );
						} else {
							
							value.element.parent().css( 'color', 'red' );
						}
					});
					
					if( ! values.includes( false ) ) {
						
						resolve( true );
						_i.publish( "data.onVerificationSuccess", {
							form: _i,
						});
					} else {
						
						_i.publish( "data.onVerificationFailure", {
							form: _i,
						});
						_i.v.message.html( codiad.common.message( "Please be sure to fill out all denoted fields", "error" ) );
						$( "html, body" ).animate( { scrollTop: 0 }, "slow" );
						reject( false );
					}
				});
			},
		};
		this.submit = function() {};
		
		_i.m.init();
		_i.v.init();
		
		if( instance !== null ) {
			
			_i.init( instance );
		}
		return _i;
	};
	
	codiad.forms.prototype.create = function( key, o ) {
		
		if( typeof o !== "object" ) {
			
			throw new Error({
				message: "Initialization of form object requires data of type Object",
				data_recieved: o,
				type_recieved: ( typeof o ),
			});
		}
		
		this.m.data[key] = this.m.create( o );
	};
	codiad.forms.prototype.get_first_page = function() {
			
			let _this = this;
			let i = null;
			
			$.each( _this.m.data, function( key, value ) {
				
				if( i === null ) {
					
					i = value.page;
				} else if( value.page > i ) {
					
					i = value.page;
				}
			});
			return i;
		},
	codiad.forms.prototype.get_total_pages = function() {
		
		let _this = this;
		let i = 0;
		$.each( _this.m.data, function( key, value ) {
			
			if( value.page > i ) {
				
				i = value.page;
			}
		});
		return i;
	};
	codiad.forms.prototype.clone = function( o ) {};
	codiad.forms.prototype.init = function( o ) {
		
		if( typeof o !== "object" ) {
			
			throw new Error({
				message: "Initialization of form object requires data of type Object",
				data_recieved: o,
				type_recieved: ( typeof o.type ),
			});
		}
		
		let _this = this;
		
		if( o.container ) {
			
			this.v.container = o.container;
		}
		
		if( ! o.data ) {
			
			o.data = {};
		}
		
		if( o.back_label ) {
			
			this.back_label = o.back_label;
		}
		
		if( o.next_label ) {
			
			this.next_label = o.next_label;
		}
		
		if( o.submit_label ) {
			
			this.submit_label = o.submit_label;
		}
		
		let previous = null;
		
		$.each( o.data, async function( key, value ) {
			
			if( value.key === undefined ) {
				
				value.key = key;
			}
			_this.create( key, value );
			
			_this.m.data[key].previous = previous;
			
			if( previous !== null ) {
				
				previous.next = _this.m.data[key];
			}
			
			previous = _this.m.data[key];
		});
		this.m.conditionals();
		this.v.render_page( 0 );
	};
})( this, jQuery );