<?php

/*
 +--------------------------------------------------------------------+
 |                                                    |
 +--------------------------------------------------------------------+
 | Copyright Sarah Gladstone (c) 2004-2010                             |
 +--------------------------------------------------------------------+
 | This is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 .   |
 |                                                                    |
 | This is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.                         |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * $Id$
 *
 */

require_once 'CRM/Contact/Form/Search/Custom/Base.php';
require_once 'CRM/Contact/BAO/SavedSearch.php';
require_once 'CRM/Contact/Form/Search/Interface.php';

class CRM_Birthdayanniversarysearch_Form_Search_AnniversarySearch extends  CRM_Contact_Form_Search_Custom_Base
   implements CRM_Contact_Form_Search_Interface {

    protected $_formValues;
    protected $_tableName = null;
    protected $_where = ' (1) ';

    function __construct( &$formValues ) {     
        $this->_formValues = $formValues;

        /**
         * Define the columns for search result rows
         */
        $this->_columns = array( 
				 ts('Name') => 'name_a',	
				 ts('Spouse Name')   => 'name_b',
				 ts('Date') => 'oc_date',
				 ts('Group Name')   => 'gname', 
				 ts('Occasion Type' ) => 'oc_type'
				 );
				 
		$this->_includeGroups   = CRM_Utils_Array::value( 'includeGroups', $this->_formValues, array( ) );
        //define variables
        $this->_allSearch = false;
        $this->_groups    = false;
        $this->_tags      = false;
        
        //make easy to check conditions for groups and tags are
        //selected or it is empty search
        if ( empty( $this->_includeGroups )) {
            //empty search
            $this->_allSearch = true;
        }
 
        if ( ! empty( $this->_includeGroups )) {
            //group(s) selected
            $this->_groups = true;
        }
    }
   
   function __destruct( ) {
        // mysql drops the tables when connectiomn is terminated
        // cannot drop tables here, since the search might be used
        // in other parts after the object is destroyed
    }


    function buildForm( &$form ) {
        /**
         * You can define a custom title for the search form
         */
         
        $groups         =& CRM_Core_PseudoConstant::group( );
        $this->setTitle('Find Upcoming Anniversaries');

        /**
         * Define the search form fields here
         */



$month =
            array( ''   => ' - select month - ' , '1' => 'January', '2' => 'February', '3' => 'March',
	 '4' => 'April', '5' => 'May' , '6' => 'June', '7' => 'July', '8' => 'August' , '9' => 'September' , '10' => 'October' , '11' => 'November' , '12' => 'December') ;
            
            
        $form->add  ('select', 'oc_month_start', ts('Start With Month'),
                     $month,
                     false);

 	$form->add  ('select', 'oc_month_end', ts('Ends With Month'),
                     $month,
                     false);

/*
         $form->add( 'text',
                    'oc_month_start',
                    ts( ' Start With Month' ) );

	$form->add( 'text',
                    'oc_month_end',
                    ts( ' End With Month' ) );


*/
	$form->add( 'checkbox',
                    'oc_deceased',
                    ts( ' Include deceased' ) );


	$form->add( 'text',
                    'oc_day_start',
                    ts( ' Start With day' ) );

	$form->add( 'text',
                    'oc_day_end',
                    ts( ' End With day' ) );

/*

 	$form->add( 'date',
                    'oc_start_date',
                    ts('Date From'),
                    CRM_Core_SelectValues::date('custom', 10, 3 ) );
        $form->addRule('oc_start_date', ts('Select a valid date.'), 'qfDate');

        $form->add( 'date',
                    'oc_end_date',
                    ts('...through'),
                    CRM_Core_SelectValues::date('custom', 10, 0 ) );
        $form->addRule('oc_end_date', ts('Select a valid date.'), 'qfDate');

*/
        /**
         * If you are using the sample template, this array tells the template fields to render
         * for the search form.
         */
        if ( count($groups) == 0) {
            CRM_Core_Session::setStatus( ts("Atleast one Group and Tag must be present, for Custom Group / Tag search.") );
            $url = CRM_Utils_System::url( 'civicrm/contact/search/custom/list', 'reset=1' );
            CRM_Utils_System::redirect($url);
        }
 
        $inG =& $form->addElement('advmultiselect', 'includeGroups',
                                  ts('Include Group(s)') . ' ', $groups,
                                  array('size'  => 5,
                                        'style' => 'width:240px',
                                        'class' => 'advmultiselect')
                                  );
        $form->assign( 'elements', array( 'oc_month_start', 'oc_month_end', 'oc_day_start', 'oc_day_end','oc_deceased','includeGroups') );


    }

    /**
     * Define the smarty template used to layout the search form and results listings.
     */
    function templateFile( ) {
       return 'CRM/Birthdayanniversarysearch/Form/Search/AnniversarySearch.tpl';
    }
       
    /**
      * Construct the search query
      */       
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false, $onlyIDs = false ) {
        
        // SELECT clause must include contact_id as an alias for civicrm_contact.id
/*
        SELECT contact_a.display_name, contact_b.display_name, rel.start_date
FROM civicrm_contact AS contact_a
LEFT JOIN civicrm_relationship AS rel ON rel.contact_id_a = contact_a.id
LEFT JOIN civicrm_contact AS contact_b ON rel.contact_id_b = contact_b.id
LEFT JOIN civicrm_relationship_type AS reltype ON reltype.ID = rel.relationship_type_id
WHERE contact_a.contact_type =  'Individual'
AND reltype.name_a_b =  'Spouse Of'
AND rel.is_active =1

*/
  
	
	/******************************************************************************/
	// Get data for contacts 

	if ( $onlyIDs ) {
        	$select  = " civicrm_contact.id as contact_id ";
    	} else {
		$select = "DISTINCT contact_a.id as contact_id, contact_a.display_name as name_a, contact_b.display_name as name_b,  CONCAT( monthname(rel.start_date), ' ', day(rel.start_date))  as oc_date , 'Anniversary' as oc_type" ;
		//$select = "DISTINCT civicrm_contact.id as contact_id, CONCAT( monthname(civicrm_contact.birth_date) , ' ',  day(civicrm_contact.birth_date)) as sort_name , civicrm_contact.display_name as name, 'birthday' as oc_type" ;

	}
	if ( $this->_groups && !$onlyIDs) {
		//unset( $this->_columns['Tag Name'] );
		$select .= ", GROUP_CONCAT(DISTINCT group_names ORDER BY group_names ASC ) as gname";
	}else{
		
		unset( $this->_columns['Group Name'] );
	} 
	$from  = $this->from( );
 	$where = $this->where( $includeContactIDs ) ; 

	//$days_after_today = ($date_range_start_tmp + $date_range_end_tmp);
	//echo "<!--  date_range: " . $date_range . " -->";
        $sql = "
SELECT $select
FROM  $from
WHERE $where ";
if ( ! $onlyIDs ) {
            $sql .= " GROUP BY contact_id ";  
   }
//order by month(birth_date), oc_day";
	
	//for only contact ids ignore order.
      if ( !$onlyIDs ) {
          // Define ORDER BY for query in $sort, with default value
          if ( ! empty( $sort ) ) {
              if ( is_string( $sort ) ) {
                  $sql .= " ORDER BY $sort ";
              } else {
                  $sql .= " ORDER BY " . trim( $sort->orderBy() );
              }
          } else {
              $sql .=   "ORDER BY month(rel.start_date), day(rel.start_date)";
          }
      }

  	if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }


        return $sql;
    }
    
  function from(){
	  $randomNum = md5( uniqid( ) );
        $this->_tableName = "civicrm_temp_custom_{$randomNum}";
 
        //block for Group search
        $smartGroup = array( );
        if ( $this->_groups || $this->_allSearch ) {
            require_once 'CRM/Contact/DAO/Group.php';
            $group = new CRM_Contact_DAO_Group( );
            $group->is_active = 1;
            $group->find();
            while( $group->fetch( ) ) {
                $allGroups[] = $group->id;
                if( $group->saved_search_id ) {
                    $smartGroup[$group->saved_search_id] = $group->id;
                    
                }
            }
            $includedGroups = implode( ',',$allGroups );
            
            if ( ! empty( $this->_includeGroups ) ) {
                $iGroups = implode( ',', $this->_includeGroups );
            } else {
                //if no group selected search for all groups
                $iGroups = null;
            }            
           $sql = "CREATE TEMPORARY TABLE Ig_{$this->_tableName} ( id int PRIMARY KEY AUTO_INCREMENT,
                                                                   contact_id int,
                                                                   group_names varchar(64)) ENGINE=HEAP";
            
            CRM_Core_DAO::executeQuery( $sql );     
            if ( $iGroups ) {
                $includeGroup =
                "INSERT INTO Ig_{$this->_tableName} (contact_id, group_names)
                 SELECT              civicrm_contact.id as contact_id, civicrm_group.title as group_name
                 FROM                civicrm_contact
                    INNER JOIN       civicrm_group_contact
                            ON       civicrm_group_contact.contact_id = civicrm_contact.id
                    LEFT JOIN        civicrm_group
                            ON       civicrm_group_contact.group_id = civicrm_group.id";
            } else {
                $includeGroup =
                "INSERT INTO Ig_{$this->_tableName} (contact_id, group_names)
                 SELECT              civicrm_contact.id as contact_id, ''
                 FROM                civicrm_contact";
            }
 
 
           if ( $iGroups ) {
                $includeGroup .= " WHERE           
                                     civicrm_group_contact.status = 'Added'  AND
                                     civicrm_group_contact.group_id IN($iGroups)";
            } else {
                $includeGroup .= " WHERE ( 1 ) ";          
            }
            
            CRM_Core_DAO::executeQuery( $includeGroup );
            
            //search for smart group contacts
 
            foreach( $this->_includeGroups as $keys => $values ) {
                if ( in_array( $values, $smartGroup ) ) {
                    
                    $ssId = CRM_Utils_Array::key( $values, $smartGroup );
                
                    $smartSql = CRM_Contact_BAO_SavedSearch::contactIDsSQL( $ssId );
                    
                    $smartSql .= " AND contact_a.id NOT IN (
                              SELECT contact_id FROM civicrm_group_contact
                              WHERE civicrm_group_contact.group_id = {$values} AND civicrm_group_contact.status = 'Removed')";
                    
                    
                    $smartGroupQuery = " INSERT IGNORE INTO Ig_{$this->_tableName}(contact_id)
                                     $smartSql";
                
                    CRM_Core_DAO::executeQuery( $smartGroupQuery );
                    $insertGroupNameQuery = "UPDATE IGNORE Ig_{$this->_tableName}
                                         SET group_names = (SELECT title FROM civicrm_group
                                                            WHERE civicrm_group.id = $values)
                                         WHERE Ig_{$this->_tableName}.contact_id IS NOT NULL
                                         AND Ig_{$this->_tableName}.group_names IS NULL";
                    CRM_Core_DAO::executeQuery($insertGroupNameQuery );
                }
            }
        }//group contact search end here; 
        $from = "  civicrm_contact AS contact_a
				   LEFT JOIN civicrm_relationship AS rel ON rel.contact_id_a = contact_a.id
				   LEFT JOIN civicrm_contact AS contact_b ON rel.contact_id_b = contact_b.id
				   LEFT JOIN civicrm_relationship_type AS reltype ON reltype.ID = rel.relationship_type_id ";
 
        /*
         * check the situation and set booleans
         */
        if ($iGroups != 0) {
            $iG = true;
        } else {
            $iG = false;
        }
        /*
         * Set from statement depending on array sel
         */
        if ($iG) {
                $from .= " INNER JOIN Ig_{$this->_tableName} temptable1 ON (contact_a.id = temptable1.contact_id)";
                $this->_where = "( temptable1.contact_id IS NOT NULL ) ";
            }
        $from .= " LEFT JOIN civicrm_email ON ( contact_a.id = civicrm_email.contact_id AND ( civicrm_email.is_primary = 1 OR civicrm_email.is_bulkmail = 1 ) )";
	return $from;
 	}
 
  function where($includeContactIDs = false){ 

/*
SELECT 




*/
	$clauses = array( );

	$oc_month_start = $this->_formValues['oc_month_start'] ;
	$oc_month_end = $this->_formValues['oc_month_end'] ;	
	
	$oc_day_start = $this->_formValues['oc_day_start'];
	$oc_day_end = $this->_formValues['oc_day_end'];
	
	$oc_deceased	= $this->_formValues['oc_deceased'];
	
	if( ($oc_month_start <> '' ) && is_numeric ($oc_month_start)){
		$clauses[] =  "month(rel.start_date) >= ".$oc_month_start ;
	}


	if( ($oc_month_end <> '' ) && is_numeric ($oc_month_end)){
		$clauses[]  = "month(rel.start_date) <= ".$oc_month_end;
	}



	if( ( $oc_day_start <> '') && is_numeric($oc_day_start) ){
		$clauses[] =  "day(rel.start_date) >= ".$oc_day_start;

	}

	if( ( $oc_day_end <> '') && is_numeric($oc_day_end) ){
		$clauses[] = "day(rel.start_date) <= ".$oc_day_end;

	}

	$clauses[] = "contact_a.contact_type = 'Individual'";
	$clauses[] = "reltype.name_a_b =  'Spouse Of'";
	$clauses[] = "rel.is_active =1";
	
	if( isset($oc_deceased) && $oc_deceased == 1 ){
		//$clauses[] = "contact_a.is_deceased = 1";
	}
	else{
		$clauses[] = "contact_a.is_deceased != 1";
	}
	
	$clauses[] = "rel.start_date IS NOT NULL";

	if ( $includeContactIDs ) {
         $contactIDs = array( );
         foreach ( $this->_formValues as $id => $value ) {
             if ( $value &&
                  substr( $id, 0, CRM_Core_Form::CB_PREFIX_LEN ) == CRM_Core_Form::CB_PREFIX ) {
                 $contactIDs[] = substr( $id, CRM_Core_Form::CB_PREFIX_LEN );
             }
         }

         if ( ! empty( $contactIDs ) ) {
                $contactIDs = implode( ', ', $contactIDs );
                $clauses[] = "contact_a.id IN ( $contactIDs )";
            }
        }
        
	 $partial_where_clause = implode( ' AND ', $clauses );

	return $partial_where_clause ;


 }	

    /* 
     * Functions below generally don't need to be modified
     */
    function count( ) {
           $sql = $this->all( );
           
           $dao = CRM_Core_DAO::executeQuery( $sql,
                                             CRM_Core_DAO::$_nullArray );
           return $dao->N;
    }
       
    function contactIDs( $offset = 0, $rowcount = 0, $sort = null) { 
        return $this->all( $offset, $rowcount, $sort, false, true );
    }
       
    function &columns( ) {
        return $this->_columns;
    }

   function setTitle( $title ) {
       if ( $title ) {
           CRM_Utils_System::setTitle( $title );
       } else {
           CRM_Utils_System::setTitle(ts('Search'));
       }
   }

   function summary( ) {
       return null;
   }

}
