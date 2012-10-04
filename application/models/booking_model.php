<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of login
 *
 * @author RAJU
 */
class Booking_model extends MY_Model { 
//put your code here
    public function __construct() {
        parent::__construct();
    }
    
    function save_booking($post=array())
    {
        //$post['url_key'] = str_replace(' ', '_', preg_replace('!\s+!', ' ', $post['title']));

        if(!empty($post['id']))
        {
            $post['application_details_id'] = $this->saveRecord(conversion($post,'application_details_lib'),'application_details',array('id'=>$post['id']));
        }
        else
        {
            $post['application_details_id'] = $this->saveRecord(conversion($post,'application_details_lib'),'application_details');
        }

        if($post['application_details_id']>0)
		{
			$this->save_other_details($post);
			if(isset($post['original_file_name']) && !empty($post['original_file_name']) )
			{
				$db_file_name = $post['db_file_name'];
				$original_file_name = $post['original_file_name'];
				$global_id = $post['application_details_id'];
				$_POST['attachments_id'] = $this->fileupload_model->save_attachment($db_file_name,$original_file_name,$global_id);
			}
		}
    }

    function save_other_details($post)
    {
        $tbl_array = array('booking_details','receipts');
        foreach($tbl_array as $table)
        {
            if(!empty($post['id']))
            {
                $this->saveRecord(conversion($post,$table.'_lib'),$table);
            }
            else
            {
                $this->saveRecord(conversion($post,$table.'_lib'),$table,array('application_details_id'=>$post['application_details_id']));
            }
        }
    }

    function getMasterData()
    {
        $blocks_sql = 'select id,name from blocks where status = "1"';
        $data['blocks'] = $this->getDBResult($blocks_sql, 'object');
        $rooms_sql = 'select id,name from rooms where status = "1"';
        $data['rooms'] = $this->getDBResult($rooms_sql, 'object');
        return $data;
    }

    function getAvaliableBlocksRooms($post)
    {
        //print_r($post);
        $from_date = isset($post['from_date'])?date('Y-m-d',strtotime($post['from_date'])):NULL;
        $to_date = isset($post['to_date'])?date('Y-m-d',strtotime($post['to_date'])-86400):NULL;

        $sql = 'SELECT r.id AS id, r.name AS roomname, b.id AS blocks_id, b.name AS blockname
                FROM rooms r
                JOIN blocks b ON r.blocks_id = b.id';
        if(isset($post['vip_quota']))
        {
            $sql .= ' WHERE r.vip_quota = 1';
        }
        $data = $this->getDBResult($sql, 'object');
        $room_details = array();
        foreach($data as $rooms_data)
        {
            $room_details[$rooms_data->blocks_id][$rooms_data->id] = array('id'=>$rooms_data->id,'roomname'=>$rooms_data->roomname,'blocks_id'=>$rooms_data->blocks_id,'blockname'=>$rooms_data->blockname);
        }
        if(($from_date != NULL) && ($to_date != NULL))
        {
            $booked_rooms_sql = 'SELECT * FROM booking_details bd
                                    WHERE "'.$from_date.'" >= bd.from_date AND "'.$from_date.'" < bd.to_date
                                    OR "'.$to_date.'" >= bd.from_date AND "'.$to_date.'" < bd.to_date';
            $booked_data = $this->getDBResult($booked_rooms_sql, 'object');
        }
        $booked_rooms = array();
        if(!empty($booked_data))
        {
            foreach($booked_data as $details)
            {
                $booked_rooms[$details->blocks_id][$details->rooms_id] = $details->rooms_id;
            }
        }
        //print_r($booked_rooms);
        //print_r($room_details);
        foreach($booked_rooms as $blockid=>$rooms)
        {
            foreach($rooms as $roomid=>$rid)
            {
                unset($room_details[$blockid][$roomid]);
            }
        }
        //print_r($room_details);
        if($post['blocks_id'] == 0)
        {
            $block_options = '<option value="0">Select Block</option>';
            foreach($room_details as $blockid=>$rooms)
            {
                if(!empty($rooms))
                {
                    $room_options = '<option value="0">Select Room</option>';
                    foreach($rooms as $roomid=>$roomdetails)
                    {
                        $room_options .= '<option value="'.$roomid.'">'.$roomdetails['roomname'].'</option>';
                        $blockname = $roomdetails['blockname'];
                    }
                    $block_options .= '<option value="'.$blockid.'">'.$blockname.'</option>';
                }
            }
            $ret_data['block_options'] = $block_options;
            $ret_data['room_options'] = $room_options;
            return $ret_data;
        }
        else
        {
            //print_r($room_details[$post['blocks_id']]);
            $room_options = '<option value="0">Select Room</option>';
            foreach($room_details[$post['blocks_id']] as $blockid=>$rooms)
            {
                $room_options .= '<option value="'.$rooms['id'].'">'.$rooms['roomname'].'</option>';
            }
            $ret_data['block_options'] = false;
            $ret_data['room_options'] = $room_options;
            return $ret_data;
        }
        //echo $block_options;
        //echo '<br>'.$room_options;
        /*$sql = 'SELECT bd.id AS bdid, r.id AS roomid, r.name AS roomname, b.id AS blockid, b.name AS blockname
                FROM rooms r
                LEFT JOIN booking_details bd ON r.id = bd.rooms_id
                LEFT JOIN blocks b ON b.id = r.blocks_id
                WHERE COALESCE("'.$from_date.'" NOT BETWEEN bd.from_date AND bd.to_date, TRUE)
                AND COALESCE("'.$to_date.'" NOT BETWEEN bd.from_date AND bd.to_date, TRUE)
                OR (bd.from_date IS NULL AND bd.to_date IS NULL) AND bd.rooms_id IS NULL';
        $data = $this->getDBResult($sql, 'object');

        foreach($data as $blocks_rooms)
        {
            $br_data['blocks'][$blocks_rooms->blockid] = $blocks_rooms->blockname;
            $br_data['rooms'][$blocks_rooms->blockid][$blocks_rooms->roomid] = $blocks_rooms->roomname;
        }
        $booked_rooms_sql = 'SELECT * FROM booking_details bd
                            WHERE "2012-09-05" BETWEEN bd.from_date AND bd.to_date
                            AND "2012-09-07" BETWEEN bd.from_date AND bd.to_date';
        $booked_rooms = $this->getDBResult($booked_rooms_sql, 'object');
        print_r($booked_rooms);
        if($post['blocks_id'] == 0)
        {
            $block_options = '<option value="0">Select Block</option>';
            foreach($br_data['blocks'] as $blockid=>$blockname)
            {
                $block_options .= '<option value="'.$blockid.'">'.$blockname.'</option>';
            }
            $room_options = '<option value="0">Select Room</option>';
            foreach($br_data['rooms'] as $blockid=>$rooms)
            {
                foreach($rooms as $roomid=>$roomname)
                {
                    $room_options .= '<option value="'.$roomid.'">'.$roomname.'</option>';
                }
            }
            $ret_data['block_options'] = $block_options;
            $ret_data['room_options'] = $room_options;
            return $ret_data;
        }
        else
        {
            $room_options = '<option value="0">Select Room</option>';
            foreach($br_data['rooms'][$post['blocks_id']] as $roomid=>$roomname)
            {
                $room_options .= '<option value="'.$roomid.'">'.$roomname.'</option>';
            }
            $ret_data['block_options'] = false;
            $ret_data['room_options'] = $room_options;
            return $ret_data;
        }*/
    }

    function getRoomBookingDates($post)
    {
        $sql = 'SELECT bd.from_date, bd.to_date FROM booking_details bd
                WHERE bd.blocks_id = "'.$post['blocks_id'].'" AND bd.rooms_id = "'.$post['rooms_id'].'"';
        $bookeddates = $this->getDBResult($sql, 'object');
        if(!empty($bookeddates))
        {
            foreach($bookeddates as $dates)
            {
                $fromdate = strtotime($dates->from_date);
                $todate = strtotime($dates->to_date);
                for($date = $fromdate;$date < $todate;$date=$date+86400)
                {
                    $booked_dates[] = array(date('m/d/Y',$date));
                }
            }
        }
        $booked_dates[] = array(); // last record empty to allow all dates to be disabled in date picker
        return $booked_dates;
    }

    function gettabledetails($tablenames=array())
    {
        $tbl_fields = new stdclass();
        foreach($tablenames as $tablename)
        {
            $sql = "show columns from `".$tablename."`";
            $fields = $this->getDBResult($sql, 'object');
            foreach($fields as $values)
            {
                $fld = $values->Field;
                $tbl_fields->$fld = '';
            }
        }
        return $tbl_fields;
    }
	public function getDayReport($date)
    {
		$user_id=1;
		$data = array();
		$sql = "select 
				b.name as blockname,r.name as roomname, 
				bd.blocks_id,bd.rooms_id,rc.advance_amount,rc.deposit_amt,rc.rent_amount,
				rc.total_amount_paid 
				from receipts rc
				left join booking_details bd on bd.application_details_id = rc.application_details_id
				left join blocks b on b.id=bd.blocks_id
				left join rooms r on r.id=bd.rooms_id 
				where rc.received_by=".$user_id." and DATE_FORMAT(rc.received_date,'%Y-%m-%d') = '".$date."' and rc.`status`=1
				order by blockname,roomname";
       // echo $sql; die;
		$bookedreport = $this->getDBResult($sql, 'object');
        
		$booked_report_arr = array();
		$con_total_amount = 0;
		if(!empty($bookedreport))
        {
            foreach($bookedreport as $val)
            {
                $booked_report_arr[$val->blockname][] = array('room_name'=>$val->roomname,
													   'advance_amount'=>$val->advance_amount,
													   'deposit_amt'=>$val->deposit_amt,
													   'rent_amount'=>$val->rent_amount,
													   'total_amount_paid'=>$val->total_amount_paid);
				$con_total_amount += $val->total_amount_paid;													   
            }
        }
		
		$sql1 = "select 
				b.name as blockname,r.name as roomname, 
				bd.blocks_id,bd.rooms_id,p.deposit_refund_amount
				from payments p 
				left join receipts rc on p.receipt_id = rc.id
				left join booking_details bd on bd.application_details_id = rc.application_details_id
				left join blocks b on b.id=bd.blocks_id
				left join rooms r on r.id=bd.rooms_id 
				where p.deposit_refund_by=".$user_id." and DATE_FORMAT(p.deposit_refund_date,'%Y-%m-%d') = '".$date."' and p.`status`=1
				order by blockname,roomname";
        //echo $sql1; die;
		$refundreport = $this->getDBResult($sql1, 'object');
        
		$refund_report_arr = array();
		$con_ref_total_amount = 0;
		if(!empty($refundreport))
        {
            foreach($refundreport as $val)
            {
                $refund_report_arr[$val->blockname][] = array('room_name'=>$val->roomname,
													   'deposit_refund_amount'=>$val->deposit_refund_amount);
				$con_ref_total_amount += $val->deposit_refund_amount;													   
            }
        }
		
		$data['con_total_amount'] =$con_total_amount;
		$data['booked_report_arr'] =$booked_report_arr;
		$data['con_ref_total_amount'] =$con_ref_total_amount;
		$data['refund_report_arr'] =$refund_report_arr;
		return $data;
	}
	
	public function getBookingDetails($app_id)
    {
		$sql = "select ad.application_id, ad.customer_id, ad.applicant_name, ad.applicant_address,
				date_format(bd.from_date,'%d/%m/%Y') as from_date, date_format(bd.to_date,'%d/%m/%Y') as to_date, 
				date_format(bd.checkout_date,'%d/%m/%Y') as checkout_date, bd.no_of_days, bd.booking_type,
				b.name as blocak_name,r.name as room_name,
				rp.deposit_amt,rp.rent_amount,rp.advance_amount,rp.total_amount_paid
				from application_details ad
				left join booking_details bd on ad.id = bd.application_details_id
				left join blocks b on bd.blocks_id = b.id
				left join rooms r on bd.rooms_id = r.id
				left join receipts rp on rp.application_details_id = ad.id
				where ad.application_id=".$app_id;
		$refundreport = $this->getDBResult($sql, 'object');
		
		return $refundreport;			
	}
}