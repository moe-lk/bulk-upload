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
        try {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln('creating : students_count_view');
            $query = DB::table('institution_students as ist')
                ->distinct(['ist.institution_id,ist.student_id,ist.academic_period_id'])
                ->select(
                    'ist.institution_id',
                    DB::raw('count(*) as total'),
                    DB::raw("SUM(CASE WHEN security_users.gender_id = 1  THEN 1 ELSE 0 END) AS male"),
                    DB::raw("SUM(CASE WHEN security_users.gender_id = 2  THEN 1 ELSE 0 END) AS female")
                )
                ->join('security_users', 'security_users.id', 'ist.student_id')
                ->groupBy('ist.institution_id');
            Schema::createOrReplaceView('students_count_view', $query);
            $output->writeln('creat : students_count_view');
        } catch (\Throwable $th) {
            $output->writeln($th->getMessage());
        }
    }

    /**
     * create student list 
     *
     * @return void
     */
    public static function createOrUpdateStudentList()
    {
        try {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln('creating : students_list_view');
            $query = DB::table('security_users as stu')
                ->distinct(['ist.institution_id,ist.student_id,ist.academic_period_id'])
                ->select(
                    "i.id as institution_id",
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
                ->leftJoin("institution_students  as ist", "ist.student_id", "stu.id")
                ->leftJoin("institutions as i", "ist.institution_id", "i.id")
                ->leftJoin("education_grades as eg", "eg.id", "i.id")
                ->leftJoin("institution_class_students as ics", "ist.student_id", "ics.student_id")
                ->leftJoin("institution_classes  as ic", "ic.id", "ics.institution_class_id")
                ->leftJoin("student_guardians  as sgf", function ($join) {
                    $join->on("sgf.student_id", "stu.id");
                    $join->where("sgf.guardian_relation_id", 1);
                })
                ->leftJoin("security_users  as sufu", "sgf.guardian_id", "sufu.id")
                ->leftJoin("student_guardians  as sgm", function ($join) {
                    $join->on("sgm.student_id", "stu.id");
                    $join->where("sgm.guardian_relation_id", 2);
                })
                ->leftJoin("security_users  as sumu", "sgm.guardian_id", "sumu.id")
                ->leftJoin("student_guardians  as sg", function ($join) {
                    $join->on("sg.student_id", "stu.id");
                    $join->where("sg.guardian_relation_id", 3);
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
                ->leftJoin("nationalities as natif", function ($join) {
                    $join->on("sufu.nationality_id", "natif.id");
                    $join->where("sgf.guardian_relation_id", 1);
                })
                ->leftJoin("nationalities as natim", function ($join) {
                    $join->on("sumu.nationality_id", "natim.id");
                    $join->where("sgm.guardian_relation_id", 2);
                })
                ->leftJoin("nationalities as natig", function ($join) {
                    $join->on("sugu.nationality_id", "natig.id");
                    $join->where("sg.guardian_relation_id", 3);
                })
                ->leftJoin("identity_types as idtf", function ($join) {
                    $join->on("sufu.nationality_id", "idtf.id");
                    $join->where("sgf.guardian_relation_id", 1);
                })
                ->leftJoin("identity_types as idtm", function ($join) {
                    $join->on("sumu.nationality_id", "idtm.id");
                    $join->where("sgm.guardian_relation_id", 2);
                })
                ->leftJoin("identity_types as idtg", function ($join) {
                    $join->on("sugu.nationality_id", "idtg.id");
                    $join->where("sg.guardian_relation_id", 3);
                })
                ->groupBy("stu.openemis_no")
                ->groupBy("i.id");
            Schema::dropIfExists("students_list_view");
            Schema::createOrReplaceView('students_list_view', $query);
            $output->writeln('created : students_list_view');
        } catch (\Throwable $th) {
            $output->writeln($th->getMessage());
        }
    }

    /**
     * Create or update Upload list view
     *
     * @return void
     */
    public static function createOrUpdateUploadList()
    {
        try {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln('creating : upload_list_view');
            $query = DB::table("uploads as up")
                ->select(
                    "i.id as institution_id",
                    "i.name as Name",
                    "i.code as Census",
                    "ic.name as Class Name",
                    "eg.name as Grade",
                    "up.filename as Filename",
                    DB::raw("(CASE 
             WHEN up.is_processed = 0 then 'Not Processed' 
             WHEN up.is_processed = 1 then 'Success' 
             WHEN up.is_processed = 2 then 'Failed' 
             WHEN up.is_processed = 3 and  up.updated_at > (hour(now())-2) then 'Terminated' 
             WHEN up.is_processed = 3 and  up.updated_at < (hour(now())-2) then 'Processing' 
             end) as Status "),
                    DB::raw("(CASE 
            WHEN up.insert = 0 then 'No Process' 
            WHEN up.insert = 1 then 'Success' 
            WHEN up.insert = 2 then 'Failed'
            WHEN up.insert = 3 and up.updated_at < (hour(now())-2) then 'Processing' 
            WHEN up.insert = 3  and up.updated_at > (hour(now())-2) then 'Terminated'
            end) as 'Insert Students'"),
                    DB::raw("(CASE 
            WHEN up.update = 0 then 'No Process' 
            WHEN up.update = 1 then 'Success' 
            WHEN up.update = 2 then 'Failed'
            WHEN up.update = 3 and up.updated_at < (hour(now())-2) then 'Processing' 
            WHEN up.update = 3  and up.updated_at > (hour(now())-2) then 'Terminated'
            end) as 'Create Students'"),
                    DB::raw("(CASE 
            WHEN up.is_email_sent = 0 then 'Not Send' 
            WHEN up.is_email_sent = 1 then 'Email Sent' 
            WHEN up.is_email_sent = 2 then 'Failed'
            end) as 'Email Status'"),
                    "up.created_at as Uploaded Date",
                    "up.updated_at as Last Processed Date"
                )
                ->join('institution_classes as ic', 'up.institution_class_id', 'ic.id')
                ->join('institutions as i', 'ic.institution_id', 'i.id')
                ->join('institution_class_grades as icg', 'ic.id', 'icg.institution_class_id')
                ->join('education_grades as eg', 'eg.id', 'icg.education_grade_id')
                ->groupBy('up.id');
            Schema::createOrReplaceView('upload_list_view', $query);
            Schema::disableForeignKeyConstraints('upload_list_view');
            $output->writeln('created : upload_list_view');
        } catch (\Throwable $th) {
            $output->writeln($th->getMessage());
        }
    }

    /**
     * Create or update upload counts
     *
     * @return void
     */
    public static function createOrUpdateUploadCount()
    {
        try {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln('creating : upload_count_view');
            $query = DB::table("uploads as up")
                ->select(
                    "i.id as institution_id",
                    "i.name as School",
                    "i.code as Census",
                    DB::raw('count(*) as total'),
                    DB::raw("SUM(CASE WHEN up.is_processed != 0 THEN 1 ELSE 0 END) as 'Total Processed'"),
                    DB::raw("SUM(CASE WHEN up.insert = 1  THEN 1 ELSE 0 END) AS 'Success Insert'"),
                    DB::raw("SUM(CASE WHEN up.insert = 2  THEN 1 ELSE 0 END) AS 'Failed Insert'"),
                    DB::raw("SUM(CASE WHEN up.insert = 3  THEN 1 ELSE 0 END) AS 'Processing Insert'"),
                    DB::raw("SUM(CASE WHEN up.update = 0  THEN 1 ELSE 0 END) AS 'Success update'"),
                    DB::raw("SUM(CASE WHEN up.update = 2  THEN 1 ELSE 0 END) AS 'Failed update'"),
                    DB::raw("SUM(CASE WHEN up.update = 3  THEN 1 ELSE 0 END) AS 'Processing update'")
                )
                ->join('institution_classes as ic', 'up.institution_class_id', 'ic.id')
                ->join('institutions as i', 'ic.institution_id', 'i.id')
                ->groupBy('i.id');
            Schema::dropIfExists("upload_count_view");
            Schema::createOrReplaceView("upload_count_view", $query);
            $output->writeln('created : upload_count_view');
        } catch (\Throwable $th) {
            $output->writeln($th->getMessage());
        }
    }

    /**
     * Create or update Institution Infor
     *
     * @return void
     */
    public static function createOrUpdateInstitutionInfo()
    {
        try {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln('creating : institution_info_view');
            $query = DB::table("institutions as i")
                ->select(
                    "i.id as institution_id",
                    "i.name as School Name",
                    "i.code as Census Code",
                    "i.address  as Address",
                    "a.name as Zone"
                )
                ->join("areas as a", "a.id", "i.area_id");
            Schema::dropIfExists("institution_info_view");
            Schema::createOrReplaceView("institution_info_view", $query);
            $output->writeln('created : institution_info_view');
        } catch (\Throwable $th) {
            $output->writeln($th->getMessage());
        }
    }

    /**
     * Create or update students count by grade view
     *
     * @return void
     */
    public static function createOrUpdateStudentsCountByGrade()
    {
        try {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln('creating : students_count_by_grade_view');
            $query = DB::table('institution_students as ist')
                ->distinct(['ist.institution_id,ist.student_id,ist.academic_period_id'])
                ->select(
                    "ist.institution_id",
                    DB::raw("(count(CASE WHEN eg.code = 'G1' THEN ist.student_id END)) as `G-1`"),
                    DB::raw("(count(CASE WHEN eg.code = 'G2' THEN ist.student_id END)) as `G-2`"),
                    DB::raw("(count(CASE WHEN eg.code = 'G3' THEN ist.student_id END)) as `G-3`"),
                    DB::raw("(count(CASE WHEN eg.code = 'G4' THEN ist.student_id END)) as `G-4`"),
                    DB::raw("(count(CASE WHEN eg.code = 'G5' THEN ist.student_id END)) as `G-5`"),
                    DB::raw("(count(CASE WHEN eg.code = 'G6' THEN ist.student_id END)) as `G-6`"),
                    DB::raw("(count(CASE WHEN eg.code = 'G7' THEN ist.student_id END)) as `G-7`"),
                    DB::raw("(count(CASE WHEN eg.code = 'G8' THEN ist.student_id END)) as `G-8`"),
                    DB::raw("(count(CASE WHEN eg.code = 'G9' THEN ist.student_id END)) as `G-9`"),
                    DB::raw("(count(CASE WHEN eg.code = 'G10' THEN ist.student_id END)) as `G-10`"),
                    DB::raw("(count(CASE WHEN eg.code = 'G11' THEN ist.student_id END)) as `G-11`"),
                    DB::raw("(count(CASE WHEN eg.code like '%G12%' THEN ist.student_id END)) as `G-12`"),
                    DB::raw("(count(CASE WHEN eg.code like '%G13%' THEN ist.student_id END)) as `G-13`")
                )
                ->join('education_grades as eg', 'eg.id', 'ist.education_grade_id')
                ->groupBy('ist.institution_id');
            Schema::dropIfExists("students_count_by_grade_view");
            Schema::createOrReplaceView('students_count_by_grade_view', $query);
            $output->writeln('created : students_count_by_grade_view');
        } catch (\Throwable $th) {
            $output->writeln($th->getMessage());
        }
    }

    /**
     * cerate or update students count by bmi
     *
     * @return void
     */
    public  static function createOrUpdateStudentCountByBMI()
    {
        try {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln('creating : students_count_by_bmi_view');
            $query = DB::table('institution_students as ist')
                ->distinct(['ist.institution_id,ist.student_id,ist.academic_period_id'])
                ->select(
                    "ist.institution_id",
                    DB::raw("count(CASE WHEN ubm.body_mass_index <  13 THEN ubm.body_mass_index END) as `Underweight`"),
                    DB::raw("count(CASE WHEN ubm.body_mass_index > 13 and ubm.body_mass_index <= 16  THEN ubm.body_mass_index END) as `Normal`"),
                    DB::raw("count(CASE WHEN ubm.body_mass_index > 16 and ubm.body_mass_index <= 18.25  THEN ubm.body_mass_index END) as `Overweight`"),
                    DB::raw("count(CASE WHEN ubm.body_mass_index > 18.25  THEN ubm.body_mass_index END) as `Severely obese`"),
                    "ist.created"
                )
                ->join("institutions as i", "i.id", "ist.institution_id")
                ->join('user_body_masses as ubm', function ($join) {
                    $join->on('ubm.security_user_id', 'ist.student_id');
                    $join->where('ubm.academic_period_id', 'ist.academic_period_id');
                })
                ->groupBy("i.id");
            Schema::dropIfExists("students_count_by_bmi_view");
            Schema::createOrReplaceView("students_count_by_bmi_view", $query);
            $output->writeln('created : students_count_by_bmi_view');
        } catch (\Throwable $th) {
            $output->writeln($th->getMessage());
        }
    }

    /**
     * create or update the students count by class
     *
     * @return void
     */
    public static function createOrUpdateStudentCountByClass()
    {
        try {
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();
            $output->writeln('creating : student_count_by_class_view');
            $query = DB::table("institution_students as ist")
                ->select(
                    "ist.institution_id",
                    "eg.name as Grade",
                    "ic.name as Class",
                    "st.first_name as 'Class Teacher'",
                    DB::raw("format(count(*),0)   as 'No of Students'")
                )
                ->distinct(['ist.institution_id,ist.student_id,ist.academic_period_id'])
                ->join("institutions as i", "i.id", "ist.institution_id")
                ->join('education_grades as eg', 'eg.id', 'ist.education_grade_id')
                ->join('institution_student_admission as isa', 'isa.student_id', 'ist.student_id')
                ->join('institution_classes as ic', 'ic.id', 'isa.institution_class_id')
                ->leftJoin('security_users as st', "st.id", "ic.staff_id")
                ->groupBy("ic.id");
            Schema::dropIfExists("student_count_by_class_view");
            Schema::createOrReplaceView('student_count_by_class_view', $query);
            $output->writeln('created : student_count_by_class_view');
        } catch (\Throwable $th) {
            $output->writeln($th->getMessage());
        }
    }
}
