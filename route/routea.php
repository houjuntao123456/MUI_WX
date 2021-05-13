<?php

/**
 * Author lxy
 * Date 2019/02/19
 * Description 路由
 */

/**
 * 货客精准 失败
 */
Route::rule('webCargoGuestLook/', 'CargoGuest/lookPeople');        //按条件查询会员
Route::rule('webCargoGuestMessage/', 'CargoGuest/messageMember');  //按条件查询人数发送短信和微信 
Route::rule('webCargoGuestFuxin/', 'CargoGuest/groupFuxin');       //按条件查询人数群发视图 
Route::rule('webCargoGuestCardM/', 'CargoGuest/groupCardM');       //不分openid群发卡券 


/**
 * 销售报表分析(SAL) 
 */
/** 机构业绩 */
Route::rule('OrgAchievementSel/', 'OrgAchievement/index');                  //机构业绩查询数据
/** 门店业绩 */ 
Route::rule('OrgAchievementStoreSel/', 'OrgAchievement/storeAchievement');  //门店业绩查询数据
/** 员工业绩 */ 
Route::rule('OrgAchievementStaffSel/', 'OrgAchievement/staffAchievement');  //员工业绩查询数据
/** 回购率 */ 
Route::rule('RepurchaseSel/', 'Repurchase/RepurchaseSel');                  //回购率查询 
Route::rule('RepurchaseTotal/', 'Repurchase/RepurchaseTotal');              //回购率查询总数据
Route::rule('RepurchaseNewMember/', 'Repurchase/newMember');                //新增会员人数 
Route::rule('RepurchaseFollowMember/', 'Repurchase/followMember');          //跟进会员人数 
Route::rule('RepurchaseBuyBack/', 'Repurchase/buyBack');                    //回购会员人数 
/** 机构PK分组 */ 
Route::rule('MOrgGroupSel/', 'MOrgGroup/groupSel');                         //机构PK分组查询
Route::rule('MOrgGroupOrgSel/', 'MOrgGroup/orgSel');                        //机构PK机构下拉框
Route::rule('MOrgGroupPlanData/', 'MOrgGroup/planData');                    //机构PK分组数据
/** 门店PK分组 */ 
Route::rule('MStoreGroupSel/', 'MStoreGroup/groupSel');                     //门店PK分组查询
Route::rule('MStoreGroupStoreSel/', 'MStoreGroup/storeSel');                //门店PK门店下拉框
Route::rule('MStoreGroupPlanData/', 'MStoreGroup/planData');                //门店PK分组数据
/** 员工PK分组 */ 
Route::rule('MStaffGroupSel/', 'MStaffGroup/groupSel');                     //员工PK分组查询
Route::rule('MStaffGroupStaffSel/', 'MStaffGroup/staffSel');                //员工PK员工下拉框
Route::rule('MStaffGroupPlanData/', 'MStaffGroup/planData');                //员工PK分组数据 
/** 员工开卡任务 */
Route::rule('MStaffCardSel/', 'MStaffCard/MStaffCardSel');                  //查询员工开卡任务数据 
/** 目标达成 */ 
Route::rule('MAttainmentSel/', 'MAttainment/attainmentSel');                //目标达成查询数据


/**
 * 员工九维绩效管理(STM)
 */
/** R活跃分析 */
Route::rule('RfmActiveStore/', 'RfmActive/storeSel');                      //查询门店
Route::rule('RfmActiveSel/', 'RfmActive/activeSel');                       //按门店查询所需数据
Route::rule('RfmActiveLook/', 'RfmActive/lookPeople');                     //按条件查询会员
Route::rule('RfmActiveMessage/', 'RfmActive/messageMember');               //按条件查询会员发送短信
Route::rule('RfmActiveGroupCardM/', 'RfmActive/groupCardM');               //按条件查询会员群发卡券
Route::rule('RfmActiveGroupCardX/', 'RfmActive/groupCardX');               //按选择的会员群发卡券
Route::rule('RfmActiveGroupFuxin/', 'RfmActive/groupFuxin');               //按条件查询会员群发富信
Route::rule('RfmActiveRfmField/', 'RfmActive/rfmField');                   //rfm添加专场会员
/** F回头率分析 */
Route::rule('RfmBackSel/', 'RfmBack/backSel');                             //按门店查询所需数据
Route::rule('RfmBackLook/', 'RfmBack/lookPeople');                         //按条件查询会员
Route::rule('RfmBackMessage/', 'RfmBack/messageMember');                   //按条件查询会员发送短信
/** M贡献度分析 */
Route::rule('RfmContributionSel/', 'RfmContribution/contributionSel');     //按门店查询所需数据
Route::rule('RfmContributionLook/', 'RfmContribution/lookPeople');         //按条件查询会员
Route::rule('RfmContributionMessage/', 'RfmContribution/messageMember');   //按条件查询会员发送短信
/** I忠诚度分析 */
Route::rule('RfmLoyaltySel/','RfmLoyalty/loyaltySel');                     //按门店查询所需数据
Route::rule('RfmLoyaltyLook/','RfmLoyalty/lookPeople');                    //按条件查询会员
Route::rule('RfmLoyaltyMessage/', 'RfmLoyalty/messageMember');             //按条件查询会员发送短信
/** N高件数分析 */
Route::rule('RfmNumberSel/', 'RfmNumber/numberSel');                       //按门店查询所需数据
Route::rule('RfmNumberLook/', 'RfmNumber/lookPeople');                     //按条件查询会员
Route::rule('RfmNumberMessage/', 'RfmNumber/messageMember');               //按条件查询会员发送短信
/** P客单价分析 */
Route::rule('RfmPriceSel/', 'RfmPrice/priceSel');                          //按门店查询所需数据
Route::rule('RfmPriceLook/', 'RfmPrice/lookPeople');                       //按条件查询会员
Route::rule('RfmPriceMessage/', 'RfmPrice/messageMember');                 //按条件查询会员发送短信
/** A件单价分析 */
Route::rule('RfmUnivalentSel/', 'RfmUnivalent/univalentSel');              //按门店查询所需数据
Route::rule('RfmUnivalentLook/', 'RfmUnivalent/lookPeople');               //按条件查询会员
Route::rule('RfmUnivalentMessage/', 'RfmUnivalent/messageMember');         //按条件查询会员发送短信
/** J高连带分析 */
Route::rule('RfmJointSel/', 'RfmJoint/jointSel');                          //按门店查询所需数据
Route::rule('RfmJointLook/', 'RfmJoint/lookPeople');                       //按条件查询会员
Route::rule('RfmJointMessage/', 'RfmJoint/messageMember');                 //按条件查询会员发送短信
/** C高消费分析 */
Route::rule('RfmConsumptionSel/', 'RfmConsumption/consumptionSel');        //按门店查询所需数据
Route::rule('RfmConsumptionLook/', 'RfmConsumption/lookPeople');           //按条件查询会员
Route::rule('RfmConsumptionMessage/', 'RfmConsumption/messageMember');     //按条件查询会员发送短信

/**
 * 互动邀约方案(IMS)
 */
/** 100天跟进 */
Route::rule('WreHundredPeople/', 'WreHundred/WreHundredPeople');      //模板查会员 
Route::rule('WreHundredList/', 'WreHundred/WreHundredList');          //100天模板查询
Route::rule('WreHundredRecord/', 'WreHundred/WreHundredRecord');      //100天互动记录 
Route::rule('WreHundredStaffSel/', 'WreHundred/staffSel');            //跟进提醒员工数据
Route::rule('WreHundredRemindSel/', 'WreHundred/remindSel');          //跟进提醒查询数据
Route::rule('WreHundredRemindPeople/', 'WreHundred/remindPeopleSel'); //跟进提醒查询会员数据
/** 专场跟进 */
Route::rule('WreFieldPeople/', 'WreField/WreFieldPeople');             //查询会员
Route::rule('WreFieldList/', 'WreField/WreFieldList');                 //模板查表
Route::rule('WreFieldRecord/', 'WreField/WreFieldRecord');             //专场互动记录 
Route::rule('WreFieldSel/', 'WreField/WreFieldSel');                   //专场查询 
Route::rule('WreFieldMember/', 'WreField/WreFieldMember');             //专场查询会员 
Route::rule('WreFieldDel/', 'WreField/WreFieldDel');                   //专场删除 
/** 生日模板 */
Route::rule('WreBirthdayList/', 'WreBirthday/WreBirthdayList');        //模板查表
Route::rule('WreBirthdayRecord/', 'WreBirthday/WreBirthdayRecord');    //生日互动记录
/**传统日子 */
Route::rule('WreTraditionList/', 'WreTradition/WreTraditionList');     //模板查表
Route::rule('WreTraditionRecord/', 'WreTradition/WreTraditionRecord'); //传统互动记录
/** 公众日子 */
Route::rule('WrePublicList/', 'WrePublic/WrePublicList');              //模板查表
Route::rule('WrePublicRecord/', 'WrePublic/WrePublicRecord');          //公众互动记录
/** 短信模板 */
Route::rule('WreMessageList/', 'WreMessage/WreMessageList');           //模板查表


/**
 * 复购流程(CPS)
 */
/** 感动策略 */
Route::rule('RemovingName/', 'Removing/removingName');              //查询感动策略名称
Route::rule('RemovingSel/', 'Removing/removingSel');                //查询感动策略数据
/** 印象策略 */
Route::rule('ReimpressionSel/', 'Reimpression/reimpressionSel');    //查询印象策略数据
/** 互动策略 */
Route::rule('ReinteractionSel/', 'Reinteraction/reinteractionSel'); //查询互动策略数据


/**
 * 卡劵管理(CTM)
 */
/** 卡券赠送 */
Route::rule('GiveCouponList/', 'GiveCoupon/couponList');          //卡券赠送列表
Route::rule('GiveCouponQuery/', 'GiveCoupon/couponQuery');        //按类型查询卡券
Route::rule('GiveCouponAdd/', 'GiveCoupon/couponAdd');            //卡券赠送
Route::rule('GiveCouponDetails/', 'GiveCoupon/couponDetails');    //卡券详情
Route::rule('GiveCouponReceive/', 'GiveCoupon/couponReceive');    //领取卡券
Route::rule('GiveCouponCardDetails/', 'GiveCoupon/cardDetails');  //记录卡券详情
/** 卡券核销 */
Route::rule('OffCouponList/', 'OffCoupon/couponList');            //卡券核销列表
Route::rule('OffCouponSel/', 'OffCoupon/offCouponSel');           //按卡号查询会员
Route::rule('OffCouponQuery/', 'OffCoupon/couponQuery');          //按卡号查询卡券
Route::rule('OffCouponWriteOff/', 'OffCoupon/writeOff');          //卡券核销
Route::rule('OffCouponSweepCodeOff/', 'OffCoupon/sweepCodeOff');  //卡券核销


/**
 * 账号设置管理(SET)
 */
/** 员工基本资料 */
Route::rule('VipinfoStaffInfo/', 'Vipinfo/staffInfo');  //基本资料


/**
 * 会员列表
 */
/** 会员修改 */
Route::rule('ViplistEdit/', 'ViplistEdit/vipEdit');               //会员修改
Route::rule('ViplistEditConsultant/', 'ViplistEdit/consultant');  //形象顾问
/** 会员资料 */
Route::rule('VipinfoSel/', 'Vipinfo/vipinfoSel');                 //会员资料
Route::rule('VipinfoFamilySel/', 'Vipinfo/familySel');            //会员资料家属查询 
Route::rule('VipinfoFamilyAdd/', 'Vipinfo/familyAdd');            //会员资料家属添加
Route::rule('VipinfoFamilyEdit/', 'Vipinfo/familyEdit');          //会员资料家属修改
Route::rule('VipinfoSelRFM/', 'RfmActive/rfmSel');                //会员资料rfm 
Route::rule('ReturnVisitList/', 'ReturnVisit/ReturnVisitList');   //会员足迹短信回访记录


/**
 * 会员端资料
 */
/** 会员资料销售流水 */
Route::rule('VipinfoSalesFlowSel/', 'Vipinfo/salesFlowSel'); //销售流水
/** 会员资料积分流水 */
Route::rule('VipinfoIntegralSel/', 'Vipinfo/integralSel');   //积分流水

/**
 * 手机扫码储值数据
 */
/** 锁客活动设置中心 */
Route::rule('SuoActivitySel/', 'SuoActivity/activitySel');      //支付锁客查询 
Route::rule('SuoActivityStoredSel/', 'SuoActivity/storedSel');  //智能储值查询 
