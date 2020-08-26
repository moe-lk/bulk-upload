<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DashboardViews;

class DashboardViewsController extends Controller
{
    
    public function callback(){
        
        /** Total number of students by institutions
         *  In Grafana query to get total students count 
         * `select total from students_count_view  where institution_id = $id`
         * `select male from students_count_view  where institution_id = $id`
         * `select female from students_count_view  where institution_id = $id`
        **/
        DashboardViews::createOrUpdateStudentCount();

        /**
         * Student list by institution
         * select * from students_list_view where institution_id = $id
         */
        DashboardViews::createOrUpdateStudentList();

        /**
         * Bulkupload files list
         * select * from upload_list_view  where institution_id = $id
         */
        DashboardViews::createOrUpdateUploadList();

        /**
         * Bulkupload counts
         * select * from upload_count_view where institution_id = $id
         */
        DashboardViews::createOrUpdateUploadCount();

        /**
         * Institution Information
         * select * from institution_info_view where institution_id = $id
         */
        DashboardViews::createOrUpdateInstitutionInfo();

        /**
         * Students count by Grade
         * select * from students_count_by_grade_view where institution_id = $id
         */
        DashboardViews::createOrUpdateStudentsCountByGrade();

         /**
         * Students count by BMI
         * select * from students_count_by_bmi_view where institution_id = $id
         */
        DashboardViews::createOrUpdateStudentCountByBMI();

        /**
         * Students count by Class table
         * select * from student_count_by_class_view where institution_id = $id
         */
        DashboardViews::createOrUpdateStudentCountByClass();
    }
    
}
