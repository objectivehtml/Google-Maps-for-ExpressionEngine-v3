/**
 * Matrix Plugin
 * 
 * This plugins creates a sync between Google Maps for ExpressionEngine
 * and a Matrix field.
 *
 * @package     Google Maps for ExpressionEngine
 * @subpackage  JavaScript API
 * @author      Justin Kimbrell
 * @copyright   Copyright (c) 2012, Objective HTML
 * @link        http://www.objectivehtml.com/google-maps/api/plugins
 * @version     1.0.6
 * @build       20120330
 */
  
$(document).ready(function() {

    /*==============*
     * Setup Plugin *
     *=========================================================================*
     *
     *  Example:
     * 
     *  var MatrixFields = [
     *      {
     *          fieldName: 'your_matrix_field_name',
     *          fieldId: 1,
     *          latColumn: 'your_lat_column_name',
     *          lngColumn: 'your_lat_column_name',
     *          titleColumn: 'your_title_column_name',
     *          contentColumn: 'your_content_column_name'
     *      },{
     *          fieldName: 'your_matrix_field_name',
     *          fieldId: 2,
     *          latColumn: 'your_lat_column_name',
     *          lngColumn: 'your_lat_column_name',
     *          titleColumn: '',
     *          contentColumn: ''
     *      }
     *  ];
     * 
     *  Tip:
     *  Leave the fields blank to omit columns.
    */

        var MatrixFields = [
            {
                fieldName: 'location_matrix_table', // The Matrix field name
                fieldId: 149, // The Matrix field id
                latColumn: 'location_matrix_lat', // The Matrix latitude column name
                lngColumn: 'location_matrix_lng', // The Matrix longtude column name
                titleColumn: 'location_matrix_title',
                contentColumn: 'location_matrix_description'
            }
        ];
        
    /*=========================================================================*/
   






    /*=====================*
     * Matrix Plugin Begin *
     *=========================================================================*/

    //If Matrix even exists
    if(typeof Matrix != "undefined") {

        //Assumes only a single Gmap is the page.
        var Gmap = GmapGlobal.object[0];
    
        // Binds the gmapInit event
        Gmap.bind('gmapInit', function(Gmap) {
            
            // Loop through the MatrixFields array
            $.each(MatrixFields, function(i, MatrixField) {
    
                var fieldName     = MatrixField.fieldName;
                var fieldId       = MatrixField.fieldId;
                var latColumn     = MatrixField.latColumn;
                var lngColumn     = MatrixField.lngColumn;
                var titleColumn   = MatrixField.titleColumn;
                var contentColumn = MatrixField.contentColumn;
                var fieldIdString = Gmap.safecracker ? fieldName : 'field_id_'+fieldId;
    
                // Loops through the Matrix instances
                $.each(Matrix.instances, function(i, obj) {
    
                    // If an instance matches the user defined then continue
                    if(fieldIdString == obj.id) {
                        
                        // Assign the global Matrix object
                        var GmapMatrix  = obj;
                        var GmapRows    = [];
    
                        /*========================*
                         * Change Matrix Defaults *
                         *========================*/
    
                        // Hide the add marker button since all new entries are added with the Gmap fieldtype
                        obj.dom.$addBtn.hide();
    
                        // Change the default menu and remove the Add Row buttons since all new data is added with the map
                        obj.menu.$ul = $('<ul id="matrix-menu" />').appendTo($(document.body)).css({
                            opacity: 0,
                            display: 'none'
                        });
    
                        obj.menu.$delete = $('<li>'+Matrix.lang.delete_row+'</li>').appendTo(obj.menu.$ul);
    
                        /*============*
                         * API Events *
                         *============*/
    
                        // Binds the gmapAddMarker event
                        Gmap.bind('gmapAddMarker', function(index, response, infowindow, Gmap) {
                            var row = GmapMatrix.addRow();
                                    
                            // Intializes a new row with the corresponding response 
                            init(row, index, response);
                        });
    
                        Gmap.bind('gmapSaveMarker', function(index, marker, infowindow, Gmap) {
                            if(GmapRows[index].title) {
                                GmapRows[index].title(marker);
                            }
    
                            if(GmapRows[index].content) {
                                GmapRows[index].content(marker);
                            }
                        });
    
                        // Binds the gmapMarkerDragEnd event
                        Gmap.bind('gmapMarkerDragEnd', function(index, response, infowindow, Gmap) {
                            // Updates the latitude and longitude after they have been changed
                            if(GmapRows[index].lat) {
                                GmapRows[index].lat(response);
                            }
    
                            if(GmapRows[index].lng) {
                                GmapRows[index].lng(response);
                            }
    
                            if(GmapRows[index].title) {
                                GmapRows[index].title(response);
                            }
                        });
    
                        // Binds the gmapRemoveMarker event
                        Gmap.bind('gmapRemoveMarker', function(index, infowindow, Gmap) {
                            //Removes the corresponding row if the marker is removed.
                            GmapRows[index].dom.$tr.remove();
                            $('<input type="hidden" name="'+GmapMatrix.id+'[deleted_rows][]" value="'+GmapRows[index].id+'" />').appendTo(GmapMatrix.dom.$field);
                        });
    
                        /*=============*
                         * Initializer *
                         *=============*/
    
                        // Intiliazes the sync between the map and matrix field
                        function init(row, index, response) {
                            var cells   = row.cells;
    
                            GmapRows[index] = row;
    
                            // Loops through each cell to find matching columns
                            $.each(cells, function(i, cell) {
    
                                var col = cell.col;
    
                                if(col.name == latColumn) {
    
                                    GmapRows[index].lat = function(response) {
                                        cell.dom.$inputs.val(response.geometry.location.lat);
                                    }
                                    
                                    GmapRows[index].lat(response);
                                }
    
                                if(col.name == lngColumn) {
    
                                    GmapRows[index].lng = function(response) {
                                        cell.dom.$inputs.val(response.geometry.location.lng);
                                    }
    
                                    GmapRows[index].lng(response);
                                }
    
                                if(col.name == titleColumn) {
    
                                    GmapRows[index].title = function(response) {
                                        var content = '';
    
                                        if(response.title && response.title != "") {
                                            content = response.title;
                                        }
                                        else {
                                            content = response.formatted_address;
                                        }
    
                                        cell.dom.$inputs.val(content);
                                    }
    
                                    GmapRows[index].title(response);
                                }
    
                                if(col.name == contentColumn) {
                                
                                    GmapRows[index].content = function(response) {
                                        cell.dom.$inputs.val(response.content);
                                    }
                                    
                                    GmapRows[index].content(response);
                                }
                                
                                row.remove = function() {
                                    row.dom.$tr.remove();
                                    Gmap.removeMarker('markers', index);
                                }
                            });
    
                            return row;
                        }
    
                        /*=====================*
                         * Init Edited Entries *
                         *=====================*/           
    
                        // If there are more than one existing rows, initialize them
                        if(obj.rows.length > 0) {
    
                            // Loop through each row and initialize each response
                            $.each(obj.rows, function(index, row) {
    
                                // Get the corresponding response
                                var response = Gmap.response.markers.results[index];
    
                                // If response exists then initialize the sync
                                if(response) {
                                    init(row, index, response);
                                }
                            });
                        }
                    }
                });
            });
        });
    }
});