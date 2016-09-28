jQuery(document).ready(function($){
  "use strict";

    // Checks the select all checkbox if all other checkboxes are checked.
    $( "input.tf_checkbox_selectall" ).each( function() {
      var optionContainer = $( this ).parent().parent();
      var allCheckboxes = optionContainer.find( 'input[type=checkbox]:not(.tf_checkbox_selectall)' );
      var allCheckboxesChecked = optionContainer.find( 'input[type=checkbox]:not(.tf_checkbox_selectall):checked' );
      if ( allCheckboxes.length === allCheckboxesChecked.length ) {
        $( this ).prop( 'checked', true );
      }
    } );

    // Check all checkboxes if selectall checkbox is checked.
    $( "input.tf_checkbox_selectall" ).change( function() {
      var optionContainer = $( this ).parent().parent();
      var allCheckboxes = optionContainer.find( 'input[type=checkbox]:not(.tf_checkbox_selectall)' );

      // Uncheck "select all", if one of the listed checkbox item is unchecked.
      if ( false == $( this ).prop( "checked" ) ) {
        allCheckboxes.prop( 'checked', false );
      } else {
        // Check "select all" if all checkbox items are checked.
        allCheckboxes.prop( 'checked', true );
      }
      allCheckboxes.trigger( 'change' );
    } );


    // Check selectall if all checkboxes are checked.
    $( 'input[type=checkbox]:not(.tf_checkbox_selectall)' ).change( function() {
      if ( ! $( this ).parent() ) {
        return;
      }
      if ( ! $( this ).parent().parent() ) {
        return;
      }

      var optionContainer = $( this ).parent().parent();
      var selectAll = optionContainer.find( 'input.tf_checkbox_selectall' );
      var allCheckboxes = optionContainer.find( 'input[type=checkbox]:not(.tf_checkbox_selectall)' );
      var allCheckboxesChecked = optionContainer.find( 'input[type=checkbox]:not(.tf_checkbox_selectall):checked' );

      if ( ! selectAll.length ) {
        return;
      }

      // Check "select all" if all checkbox items are checked.
      if ( allCheckboxes.length === allCheckboxesChecked.length ) {
        selectAll.prop( 'checked', true );
      } else {
        selectAll.prop( 'checked', false );
      }

    } );

});
