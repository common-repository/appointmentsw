<?php
/**
*@author  Xu Ding
*@email   thedilab@gmail.com
*@website http://www.StarTutorial.com
**/
class CalendarSettings {  

    private $dayLabels = array();

    private $currentYear=0;

    private $currentMonth=0;

    private $currentDay=0;

    private $currentDate=null;

    private $daysInMonth=0;

    private $naviHref= null;

    private $holidays = null;

    /**
     * Constructor
     */
    public function __construct(){
    	$this->naviHref = htmlentities($_SERVER['PHP_SELF']);
    }

    /**
    * print out the calendar
    */
    public function show() {
        $year  = null;

        $month = null;

        if(null==$year&&isset($_GET['years'])){
            $year = intval( $_GET['years'] );
        }else if(null==$year){
            $year = date("Y",time());
        }

        if(null==$month&&isset($_GET['month'])){
            $month = intval( $_GET['month'] );
        }else if(null==$month){
            $month = date("m",time());
        }

        $this->currentYear=$year;

        $this->currentMonth=$month;

        $this->daysInMonth=$this->_daysInMonth($month,$year);

        $this->holidays = AppointmentSw::getHolidays( $month, $year );

		$content = '<form action="" method="post">';
        $content .= '<input type="hidden" name="action" value="appointmentsw-save-settings" />';
        $content .= '<input type="hidden" name="month" value="' . $month . '" />';
        $content .= '<input type="hidden" name="years" value="' . $year . '" />';
        $content .= wp_nonce_field ( 'appointmentsw', 'appointmentsw-nonce', true, false );

        $content .= '<div id="calendar">'.
                        '<div class="box">'.
                        $this->_createNavi().
                        '</div>'.
                        '<div class="box-content">'.
                                '<ul class="label">'.$this->_createLabels().'</ul>';
                                $content.='<div class="clear"></div>';
                                $content.='<ul class="dates">';
                                 
                                $weeksInMonth = $this->_weeksInMonth($month,$year);
                                // Create weeks in a month
                                for( $i=0; $i<$weeksInMonth; $i++ ){
                                     
                                    //Create days in a week
                                    for($j=1;$j<=7;$j++){
                                        $content.=$this->_showDay($i*7+$j);
                                    }
                                }
                                 
                                $content.='</ul>';
                                 
                                $content.='<div class="clear"></div>';
             
                        $content.='</div>';
                 
        $content.='</div>';
        $content .= '<div class="center"><br> <input type="submit" value="Guardar"  style="background-color:#0c61b4;color:white;"/></div>';
        
        $content .= '</form>';
        return $content;   
    }
     
    /********************* PRIVATE **********************/
    /**
    * create the li element for ul
    */
    private function _showDay($cellNumber){

        if($this->currentDay==0){

            $firstDayOfTheWeek = date('N',strtotime($this->currentYear.'-'.$this->currentMonth.'-01'));

            if(intval($cellNumber) == intval($firstDayOfTheWeek)){

                $this->currentDay=1;

            }
        }

        if( ($this->currentDay!=0)&&($this->currentDay<=$this->daysInMonth) ){

            $this->currentDate = date('Y-m-d',strtotime($this->currentYear.'-'.$this->currentMonth.'-'.($this->currentDay)));

            $cellContent = $this->currentDay;

            $this->currentDay++;

        }else{
            $this->currentDate =null;
            $cellContent=null;
        }

        $output = "";
        $output .= '<li id="li-'.$this->currentDate.'" class="'.($cellNumber%7==1?' start ':($cellNumber%7==0?' end ':' ')).
                ($cellContent==null?'mask':'').'">'.$cellContent;

        if ( $this->currentDate !== null ) {
	        
        	$holiday_0 = isset( $this->holidays[$this->currentDate . '_0'] )?$this->holidays[$this->currentDate . '_0']:null;
        	$holiday_1 = isset( $this->holidays[$this->currentDate . '_1'] )?$this->holidays[$this->currentDate . '_1']:null;
        	 
        	$selected_0 = ($holiday_0==null)?"":"checked";
        	$selected_1 = ($holiday_1==null)?"":"checked";
        	 
	        $output .= '<input type="hidden" name="date[]" value="' . $this->currentDate . '"></input>';
	        $output .= '<div style="clear:both;">';
	        $output .= '<input type="checkbox" name="slot[]" value="' . $this->currentDate . '_0" ' . $selected_0 . ' ><span>M</span>';
	        $output .= '</div>';
	        $output .= '<div style="clear:both;">';
	        $output .= '<input type="checkbox" name="slot[]" value="' . $this->currentDate . '_1" ' . $selected_1 . ' ><span>T</span>';
	        $output .= '</div>';
	        
	    }

        $output .= '</li>';

        return $output;
    }

    /**
    * create navigation
    */
    private function _createNavi(){
         
        $nextMonth = $this->currentMonth==12?1:intval($this->currentMonth)+1;
         
        $nextYear = $this->currentMonth==12?intval($this->currentYear)+1:$this->currentYear;
         
        $preMonth = $this->currentMonth==1?12:intval($this->currentMonth)-1;
         
        $preYear = $this->currentMonth==1?intval($this->currentYear)-1:$this->currentYear;

        return
            '<div class="header">'.
                '<a class="prev" href="'.$this->naviHref.'?month='.sprintf('%02d',$preMonth).'&years='.$preYear.'">' . __( "Previous", 'appointmentsw' ) . '</a>'.
                    '<span class="title">'. __(date('M',strtotime($this->currentYear.'-'.$this->currentMonth.'-1'))) . date(' Y',strtotime($this->currentYear.'-'.$this->currentMonth.'-1')) .'</span>'.
                '<a class="next" href="'.$this->naviHref.'?month='.sprintf("%02d", $nextMonth).'&years='.$nextYear.'">' . __( "Next", 'appointmentsw' ) . '</a>'.
            '</div>';
    }

    /**
    * create calendar week labels
    */
    private function _createLabels(){  
    	$this->dayLabels[] = __( "Mon", 'appointmentsw' );
    	$this->dayLabels[] = __( "Tue", 'appointmentsw' );
    	$this->dayLabels[] = __( "Wed", 'appointmentsw' );
    	$this->dayLabels[] = __( "Thu", 'appointmentsw' );
    	$this->dayLabels[] = __( "Fri", 'appointmentsw' );
    	$this->dayLabels[] = __( "Sat", 'appointmentsw' );
    	$this->dayLabels[] = __( "Sun", 'appointmentsw' );

    	$content='';

        foreach($this->dayLabels as $index=>$label){
            $content.='<li class="'.($label==6?'end title':'start title').' title">'.$label.'</li>';
        }

        return $content;
    }

	/**
	* calculate number of weeks in a particular month
	*/
    private function _weeksInMonth($month=null,$year=null){
         
        if( null==($year) ) {
            $year =  date("Y",time());
        }
         
        if(null==($month)) {
            $month = date("m",time());
        }
         
        // find number of days in this month
        $daysInMonths = $this->_daysInMonth($month,$year);
         
        $numOfweeks = ($daysInMonths%7==0?0:1) + intval($daysInMonths/7);
         
        $monthEndingDay= date('N',strtotime($year.'-'.$month.'-'.$daysInMonths));
         
        $monthStartDay = date('N',strtotime($year.'-'.$month.'-01'));
         
        if($monthEndingDay<$monthStartDay){
             
            $numOfweeks++;
         
        }
         
        return $numOfweeks;
    }
 
    /**
    * calculate number of days in a particular month
    */
    private function _daysInMonth($month=null,$year=null){
         
        if(null==($year))
            $year =  date("Y",time());
 
        if(null==($month))
            $month = date("m",time());
             
        return date('t',strtotime($year.'-'.$month.'-01'));
    }
     
}
