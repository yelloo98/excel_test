<?php
namespace App\Http\Controllers;

use App\Model\Address;
use App\Model\Merchant;
use App\Model\User;
use App\Model\UserAccount;
use App\Model\UserRole;
use DB;
use Excel;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Mockery\Exception;

Class ExcelController {

    /**
     * 엑셀 업로드
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function UploadExcel(Request $request) {
        $file = $request->file( 'file' );
        if($file == null) { return redirect()->back()->with('flash_error', '파일을 첨부해주세요.'); }

        //# 0번째 시트 선택
        $reader = Excel::selectSheetsByIndex( 0 )->load($file->getRealPath());
        //# 시트정보
        $data = $reader->get();
        //# 해더 정보
        $heading = $data->getHeading();
        //# 데이터 정보
        $dataArr = $data->toArray();

        $excelRow = 0;

        DB::beginTransaction(); //# 트랜잭션 처리
        try {
            foreach ( $dataArr as $item ) {

                $address = new Address();
                $user = new User();
                $merchant = new Merchant();
                $userRole = new UserRole();
                $userAccount = new UserAccount();

                foreach ( $heading as $row ) {
                    if ( $row != "" ) {
                        switch ( $row ) {
                            case '상호': {
                                if($item[ $row ] == null) throw new Exception("상호는 필수값입니다.**");
                                $address->detail_addr = $item[ $row ];
                                $merchant->reg_name = $item[ $row ];
                                break;
                            }
                            case '사업자번호': {
                                if($item[ $row ] == null) break;
                                $merchant->reg_no = $item[ $row ];
                                break;
                            }

                            case '대표자': {
                                if($item[ $row ] == null) break;
                                $user->name = $item[ $row ];
                                $merchant->reg_rep_name = $item[ $row ];
                                $merchant->name = $item[ $row ];
                                break;
                            }
                            case '주소': {
                                if($item[ $row ] == null) throw new Exception("주소는 필수값입니다.**");
                                $address->road_addr = $item[ $row ];
                                $itemArr = explode(' ',$item[ $row ]);
                                if(count($itemArr) >= 4){
                                    $address->do_addr = $itemArr[0];
                                    $address->si_addr = $itemArr[1];
                                    $address->comm_addr1 = $itemArr[2];
                                    $address->comm_addr2 = $itemArr[3];
                                }
                                break;
                            }
                            case '면/리': {
                                if($item[ $row ] == null) break;
                                $address->comm_addr2 = $item[ $row ];
                                break;
                            }
                            case '우편번호': {
                                if($item[ $row ] == null) break;
                                $address->post_code = $item[ $row ];
                                break;
                            }
                            case '매장연락처': {
                                if($item[ $row ] == null) break;
                                $merchant->reg_rep_phone = $item[ $row ];
                                break;
                            }
                            case '핸드폰번호': {
                                if($item[ $row ] == null) throw new Exception("핸드폰번호는 필수값입니다.**");
                                if(User::where('phone',$item[ $row ])->count() != 0) throw new Exception("이미 등록된 사용자입니다.**");
                                $user->uid = $item[ $row ];
                                $user->token = $item[ $row ];
                                $user->phone = $item[ $row ];
                                $merchant->phone = $item[ $row ];
                                if($merchant->reg_rep_phone == null) $merchant->reg_rep_phone = $item[ $row ];
                                break;
                            }
                            case '은행명': {
                                if($item[ $row ] == null) break;
                                if(str_contains($item[ $row ],'산업')) { $userAccount->bank_id = 1; }
                                elseif(str_contains($item[ $row ],'기업')) { $userAccount->bank_id = 2; }
                                elseif(str_contains($item[ $row ],'국민')) { $userAccount->bank_id = 3; }
                                elseif(str_contains($item[ $row ],'외환')) { $userAccount->bank_id = 4; }
                                elseif(str_contains($item[ $row ],'수협')) { $userAccount->bank_id = 5; }
                                elseif(str_contains($item[ $row ],'농협')) { $userAccount->bank_id = 6; }
                                elseif(str_contains($item[ $row ],'우리')) { $userAccount->bank_id = 7; }
                                elseif(str_contains($item[ $row ],'SC')) { $userAccount->bank_id = 8; }
                                elseif(str_contains($item[ $row ],'한국씨티')) { $userAccount->bank_id = 9; }
                                elseif(str_contains($item[ $row ],'새마을')) { $userAccount->bank_id = 10; }
                                elseif(str_contains($item[ $row ],'신협')) { $userAccount->bank_id = 11; }
                                elseif(str_contains($item[ $row ],'우체국')) { $userAccount->bank_id = 12; }
                                elseif(str_contains($item[ $row ],'하나')) { $userAccount->bank_id = 13; }
                                else { throw new Exception("지원하지 않는 은행입니다.**"); }
                                break;
                            }
                            case '계좌번호': {
                                if($item[ $row ] == null) break;
                                $userAccount->bank_acct = $item[ $row ];
                                break;
                            }
                            case '생년월일': {
                                if($item[ $row ] == null) break;
                                $user->birthday = '19'.$item[ $row ];
                                break;
                            }
                            case '남/녀': {
                                if($item[ $row ] == null) break;
                                if($item[ $row ] == 1) { $user->gender = 'M'; }
                                elseif($item[ $row ] == 2) { $user->gender = 'F'; }
                                break;
                            }
                            case '사업자유형': {
                                if($item[ $row ] == null) break;
                                if(str_contains($item[ $row ],'비영리')) { $merchant->reg_business_type_id = 1; }
                                elseif(str_contains($item[ $row ],'일반')) { $merchant->reg_business_type_id = 2; }
                                elseif(str_contains($item[ $row ],'간이')) { $merchant->reg_business_type_id = 3; }
                                elseif(str_contains($item[ $row ],'면세')) { $merchant->reg_business_type_id = 4; }
                                break;
                            }
                            case '과세/비과세': {
                                if($item[ $row ] == null) break;
                                if(str_contains($item[ $row ],'과세')) { $merchant->reg_tax_type_id = 1; }
                                elseif(str_contains($item[ $row ],'비과세')) { $merchant->reg_tax_type_id = 2; }
                                break;
                            }
                            case '업종': {
                                if($item[ $row ] == null) break;
                                if(str_contains($item[ $row ],'음식점')) { $merchant->business_line_type_id = 1; }
                                elseif(str_contains($item[ $row ],'시장') || str_contains($item[ $row ],'슈퍼')) { $merchant->business_line_type_id = 2; }
                                elseif(str_contains($item[ $row ],'의료')) { $merchant->business_line_type_id = 3; }
                                elseif(str_contains($item[ $row ],'관광') || str_contains($item[ $row ],'문화')) { $merchant->business_line_type_id = 4; }
                                elseif(str_contains($item[ $row ],'숙박')) { $merchant->business_line_type_id = 5; }
                                elseif(str_contains($item[ $row ],'서비스')) { $merchant->business_line_type_id = 6; }
                                else { $merchant->business_line_type_id = 7; }
                                break;
                            }
                            case '모집인': {
                                if($item[ $row ] == null) break;
                                $merchant->recommender = $item[ $row ];
                                break;
                            }
                            case 'lat': {
                                if($item[ $row ] == null) break;
                                $address->lat = $item[ $row ];
                                break;
                            }
                            case 'lng': {
                                if($item[ $row ] == null) break;
                                $address->lng = $item[ $row ];
                                break;
                            }
                        }
                    }
                }
                //# Address
                $address->save();

                //# User
                $user->address_id = $address['id'];
                $user->password = '0000';
                $user->save();

                //# UserRole
                $userRole->user_id = $user['id'];
                $userRole->role_id = '3';
                $userRole->save();

                //# Merchant
                $merchant->association_type_id = '9999';
                $merchant->address_id = $address['id'];
                $merchant->user_id = $user['id'];
                $merchant->save();

                //# UserAccount
                $userAccount->user_id = $user['id'];
                $userAccount->merchant_id = $merchant['id'];
                $res = $userAccount->save();

                if($res<=0) { return redirect()->back()->with('flash_error', '저장 중 오류가 발생하였습니다.'); }
                $excelRow += $res;
            }
        } catch(QueryException $e) {
            $errMsg = "오류발생 (Excel line : ".($excelRow+2).")";
            DB::rollback(); //예외 발생시 롤백

            return redirect()->back()->with('flash_error', $errMsg);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $errMsg = "오류발생 (Excel line : ".($excelRow+2).")";

            if(strpos($msg, '**') !== false) {
                $errMsg .= ' - '. str_replace('**', '', $msg);
            }
            DB::rollback(); //예외 발생시 롤백
            return redirect()->back()->with('flash_error', $errMsg);
        }

        DB::commit(); //커밋
        return redirect('/')->with('flash_message', '총 '.$excelRow.' 건의 주문이 등록되었습니다.');
    }
}

