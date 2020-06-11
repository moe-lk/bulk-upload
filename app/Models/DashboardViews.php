<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelMigrationViews\Facades\Schema;

class DashboardViews extends Model
{
    /**
     * create or update student's count
     *
     * @return void
     */
    public static function createOrUpdateStudentCount()
    {
        $query = DB::table('institution_students')
            ->distinct(['institution_id,student_id,academic_period_id'])
            ->select(
                'institution_id',
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN security_users.gender_id = 1  THEN 1 ELSE 0 END) AS male"),
                DB::raw("SUM(CASE WHEN security_users.gender_id = 2  THEN 1 ELSE 0 END) AS female")
            )
            ->join('security_users', 'security_users.id', 'institution_students.student_id')
            ->groupBy('institution_students.institution_id');
        Schema::createOrReplaceView('students_count', $query);
    }

    /**
     * create student list 
     *
     * @return void
     */
    public static function createOrUpdateStudentList()
    {
        $query = DB::table('institution_students as ist')
            ->distinct(['institution_id,student_id,academic_period_id'])
            ->select("i.id as institution_id" ,
                DB::raw("eg.name as `Grade`"),
                DB::raw("stu.openemis_no as `Student ID`"),
                DB::raw("stu.first_name as `Full Name`"),
                DB::raw("GROUP_CONCAT(DISTINCT edus.name) as `Subjects`"),
                DB::raw("IFNULL(LEFT(RIGHT(count(DISTINCT edus.id),8),4),'NA') as `Number of Subjects`"),
                DB::raw("DATE_FORMAT(stu.date_of_birth ,'%W, %M %e, %Y ' ) as `Date of Birth`"),
                DB::raw("g.name as `Gender`"),
                DB::raw("IFNULL(nati.name, 'NA') as `Nationality`"),
                DB::raw("IFNULL(idt.national_code, 'NA') as `Identity Type`"),
                DB::raw("IFNULL(LEFT(RIGHT(stu.identity_number,8),4),'NA') as `Identity Number`"),
                DB::raw("IFNULL (stu.identity_number,'na') as `Testing`"),
                DB::raw("ic.name as `Class`"),
                DB::raw("IFNULL(special_need_types.name, 'NA') as `Special Need Type`"),
                DB::raw("IFNULL(special_need_difficulties.name, 'NA') as `Special Need`"),
                DB::raw("IFNULL(stu.address, 'NA') as `Address`"),
                DB::raw("IFNULL(acp.name, 'NA') as `BMI Academic Period`"),
                DB::raw("DATE_FORMAT(bmit.date,'%W, %M %e, %Y ') as `BMI Date`"),
                DB::raw("IFNULL(bmit.height, 'NA') as `Height`"),
                DB::raw("IFNULL(bmit.weight, 'NA') as `Weight`"),
                DB::raw("IFNULL(acps.name, 'NA') as `Academic Periods`"),
                DB::raw("DATE_FORMAT(ist.start_date,'%W, %M %e, %Y ') as `Start Date`"),
                DB::raw("IFNULL(bro.name, 'NA') as `Birth Registrar Office`"),
                DB::raw("IFNULL(ds.name, 'NA') as `DS Office`"),
                DB::raw("stu.address_area_id"),
                DB::raw("IFNULL(sufu.first_name, 'NA') as `Father's Name`"),
                DB::raw("IFNULL(natif.name, 'NA') as `Father's Nationality`"),
                DB::raw("DATE_FORMAT(sufu.date_of_birth,'%W, %M %e, %Y ') as `Father's Date Of Birth`"),
                DB::raw("IFNULL(sufu.address , 'N/A') as `Father's Address`"),
                DB::raw("IFNULL(idtf.national_code, 'NA') as `Father's Identity Type`"),
                DB::raw("IFNULL(sufu.identity_number, 'NA') as `Father's Identity Number`            "),
                DB::raw("IFNULL(sumu.first_name, 'N/A') as `Mothers's Name`"),
                DB::raw("IFNULL(natim.name, 'NA') as `Mothers's Nationality`"),
                DB::raw("DATE_FORMAT(sumu.date_of_birth,'%W, %M %e, %Y ') as `Mothers's Date Of Birth`"),
                DB::raw("IFNULL(sumu.address , 'N/A') as `Mothers's Address`"),
                DB::raw("IFNULL(idtm.national_code, 'NA') as `Mothers's Identity Type`"),
                DB::raw("IFNULL(sumu.identity_number, 'NA') as `Mother's Identity Number`"),
                DB::raw("IFNULL(sugu.first_name , 'N/A') as `Guardian's Name`"),
                DB::raw("IFNULL(natig.name, 'NA') as `Guardian's Nationality`"),
                DB::raw("DATE_FORMAT(sugu.date_of_birth,'%W, %M %e, %Y ') as `Guardian's Date Of Birth`"),
                DB::raw("IFNULL(sugu.address , 'N/A') as `Guardian's Address`"),
                DB::raw("IFNULL(idtg.national_code, 'NA') as `Guardian's Identity Type`"),
                DB::raw("IFNULL(sugu.identity_number, 'NA') as `Guardian's Identity Number`"),
                DB::raw("IFNULL(ubm.body_mass_index , 'N/A') as `BMI`")
            )
            ->leftJoin("institutions as i", "ist.institution_id", "i.id")
            ->leftJoin("education_grades as eg", "eg.id", "i.id")
            ->leftJoin("institution_class_students as ics", "ist.student_id", "ics.student_id")
            ->leftJoin("institution_classes  as ic", "ic.id", "ics.institution_class_id")
            ->leftJoin("security_users  as stu", "ist.student_id", "stu.id")
            ->leftJoin("student_guardians  as sgf",function($join){
                $join->on( "sgf.student_id", "stu.id");
                $join->where("sgf.guardian_relation_id",1);
            })
            ->leftJoin("security_users  as sufu", "sgf.guardian_id", "sufu.id")
            ->leftJoin("student_guardians  as sgm",function($join){
                $join->on( "sgm.student_id", "stu.id");
                $join->where("sgm.guardian_relation_id",2);
            })
            ->leftJoin("security_users  as sumu", "sgm.guardian_id", "sumu.id")
            ->leftJoin("student_guardians  as sg",function($join){
                $join->on( "sg.student_id", "stu.id");
                $join->where("sg.guardian_relation_id",3);
            })
            ->leftJoin("security_users  as sugu", "sg.guardian_id", "sugu.id")
            ->leftJoin("user_body_masses  as ubm", "ubm.security_user_id", "ist.student_id")
            ->leftJoin("genders as g", "stu.gender_id", "g.id")
            ->leftJoin("area_administratives as bro", "stu.birthplace_area_id", "bro.id")
            ->leftJoin("area_administratives as ds", "stu.address_area_id", "ds.id")
            ->leftJoin("nationalities as nati", "stu.nationality_id", "nati.id")
            ->leftJoin("identity_types as idt", "stu.nationality_id", "idt.id")
            ->leftJoin("user_special_needs", "ist.id", "user_special_needs.security_user_id")
            ->leftJoin("special_need_types", "special_need_types.id", "user_special_needs.special_need_type_id")
            ->leftJoin("special_need_difficulties", "special_need_difficulties.id", "user_special_needs.special_need_difficulty_id")
            ->leftJoin("user_body_masses as bmit", "stu.id", "bmit.security_user_id")
            ->leftJoin("academic_periods as acp", "acp.id", "bmit.academic_period_id")
            ->leftJoin("academic_periods as acps", "acps.id", "ist.academic_period_id")
            ->leftJoin("institution_subject_students as iss", "stu.id", "iss.student_id")
            ->leftJoin("education_subjects as edus", "edus.id", "iss.education_subject_id")
            ->leftJoin("nationalities as natif",function($join){
                $join->on( "sufu.nationality_id", "natif.id");
                $join->where("sgf.guardian_relation_id",1);
            })
            ->leftJoin("nationalities as natim",function($join){
                $join->on( "sumu.nationality_id", "natim.id");
                $join->where("sgm.guardian_relation_id",2);
            })
            ->leftJoin("nationalities as natig",function($join){
                $join->on( "sugu.nationality_id", "natig.id");
                $join->where("sg.guardian_relation_id",3);
            })
            ->leftJoin("identity_types as idtf",function($join){
                $join->on( "sufu.nationality_id", "idtf.id");
                $join->where("sgf.guardian_relation_id",1);
            })
            ->leftJoin("identity_types as idtm",function($join){
                $join->on( "sumu.nationality_id", "idtm.id");
                $join->where("sgm.guardian_relation_id",2);
            })
            ->leftJoin("identity_types as idtg",function($join){
                $join->on( "sugu.nationality_id", "idtg.id");
                $join->where("sg.guardian_relation_id",3);
            })
            ->groupBy("stu.openemis_no")
            ->groupBy("i.id");
        Schema::createOrReplaceView('students_list', $query);
    }
}
