<?php

/**
 * Login 
 */
Route::rule('Login/', 'Login/login');                      // 登录 
Route::rule('logout/', 'Login/logout');                    // 切换用户 
Route::rule('setMyselfPass/', 'Login/setMyselfPass');      // 修改密码
Route::rule('dataBase/', 'Login/database');                 // 获取数据库名

/**
 * Userlist 会员列表     
 */

Route::rule('UserlistlabelInfoq/', 'Userlist/labelInfoq');      // 筛选中标签
Route::rule('Userlist/', 'Userlist/list');                      // 会员列表路由  
Route::rule('UserlistMemberdata/', 'Userlist/memberdata');      // 会员资料  
Route::rule('UserlistMemberlevel/', 'Userlist/memberlevel');    // 会员等级  
Route::rule('UserlistLevellist/', 'Userlist/levellist');        // 会员等级下一级等级信息  
Route::rule('UserlistNewentry/', 'Userlist/newentry');          // 新入会员 
Route::rule('UserlistActive/', 'Userlist/active');              // 活跃会员  
Route::rule('Userlistlabel/', 'Userlist/label');                // 会员标签   
Route::rule('Userlistlabelinfo/', 'Userlist/labelInfo');        // 点击会员标签之后 
Route::rule('UserlistclickLabel/', 'Userlist/clickLabel');      // 点击标签放入缓存  
Route::rule('UserlisteditLabel/', 'Userlist/editLabel');        // 编辑会员标签
// Route::rule('UserlistSnapshot/', 'Userlist/snapshot');          // 快照  
Route::rule('UserlistSellinginfo/', 'Userlist/sellinginfo');    // 快照详情
Route::rule('UserlistManualupgrade/', 'Userlist/Manualupgrade'); // 修改会员等级   

Route::rule('UserlistVisit/', 'Userlist/visit');                // 点击会员资料中电话修改最后回访时间  
Route::rule('UserlistOfflineStore/', 'Userlist/offlineStore');  // 线下门店地址信息  

Route::rule('UserlistwapFilterlist/', 'Userlist/wapFilterlist');    // 筛选器列表
Route::rule('UserlistwapFilter/', 'Userlist/wapFilter');            // 点击列表查询


Route::rule('UserlistOrg/', 'Userlist/org');                // 组织机构  
Route::rule('UserlistStore/', 'Userlist/store');            // 门店  
Route::rule('Userlistwsstoree/', 'Userlist/wsstore');       // 无限制门店  
Route::rule('Userlistwstaff/', 'Userlist/wstaff');          // 无限制员工
Route::rule('UserlistStaff/', 'Userlist/staff');            // 员工  
Route::rule('UserlistDroplevel/', 'Userlist/drop_level');   // 会员级别下拉框

Route::rule('UserlistAdvertisement/', 'Userlist/advertisement');    // 会员中心广告
Route::rule('UserlistUploadImg/', 'Userlist/uploadImg');            // 图片上传  


Route::rule('UserlistViplist/', 'Userlist/viplist');           //首页卡片的员工信息   

Route::rule('UserlistIntroducedMe/', 'Userlist/introducedMe');                      //会员资料 介绍
Route::rule('UserlistIntroducedRemarks/', 'Userlist/introducedRemarks');            //会员资料 编辑介绍备注
Route::rule('UserlistExclusive/', 'Userlist/exclusive');                            //会员资料 专属   
Route::rule('UserlistEditExclusive/', 'Userlist/editExclusive');                    //会员资料 编辑专属

Route::rule('ConsumerAdvertising/', 'Userlist/consumerAdvertising');   //查询消费广告



Route::rule('OrderManagement/', 'OrderManagement/index');                   // 订单详情
Route::rule('OrderManagementSee/', 'OrderManagement/see');                  // 查看订单
Route::rule('OrderManagementMstatus/', 'OrderManagement/m_status');         // 修改订单互动状态
Route::rule('OrderManagementGoodsname/', 'OrderManagement/goodsname');      // 点击添加明细中商品列表
Route::rule('OrderManagementDetailed/', 'OrderManagement/detailed');        // 点击商品添加到商品明细

/**
 * AddMember 新建会员  
 */
Route::rule('AddMember/', 'AddMember/add');             // 添加
Route::rule('Register/', 'AddMember/register');         // 新会员注册
Route::rule('Introducer/', 'AddMember/introducer');     // 会员注册时查询转介绍人


/**
 * Orgmicromember 机构微会员  
 */
Route::rule('Orgmicromember/', 'Orgmicromember/index');         //机构微会员信息内容
Route::rule('OrgWMemberNewly/', 'Orgmicromember/wMemberNewly'); //查看微会员信息
Route::rule('OrgXMemberNewly/', 'Orgmicromember/XMemberNewly'); //查看消费微会员信息

/**
 * Storemicromember 门店微会员  
 */
Route::rule('Storemicromember/', 'Storemicromember/index'); //门店微会员信息内容  

/**
 * Staffmicromember 员工微会员
 */
Route::rule('Staffmicromember/', 'Staffmicromember/index'); //员工微会员信息内容  

/**
 * 下拉框信息
 */
Route::rule('Downselection/', 'Userlist/select'); // 下拉框


/**
 * 8维RFM分析
 */
Route::rule('ModelanalysisM/', 'Modelanalysis/M'); // M消费总金额分析
Route::rule('ModelanalysisC/', 'Modelanalysis/C'); // C年消费分析
Route::rule('ModelanalysisJ/', 'Modelanalysis/J'); // J消费连带率分析
Route::rule('ModelanalysisR/', 'Modelanalysis/R'); // R会员活跃度分析
Route::rule('ModelanalysisP/', 'Modelanalysis/P'); // P消费客单价分析
Route::rule('ModelanalysisN/', 'Modelanalysis/N'); // N消费件数分析
Route::rule('ModelanalysisF/', 'Modelanalysis/F'); // F消费次数分析
Route::rule('ModelanalysisA/', 'Modelanalysis/A'); // A消费件单价分析
Route::rule('ModelanalysisI/', 'Modelanalysis/I'); // I转介绍人数分析


/**
 * 卡劵列表   
 */
Route::rule('CouponListIndex/', 'CouponList/index');        // 卡劵列表  
Route::rule('CouponListmyCoupon/', 'CouponList/myCoupon');  // 我的卡劵  
Route::rule('CouponListDetails/', 'CouponList/details');    // 卡劵详情
Route::rule('CouponListMalist/', 'CouponList/malist');      // 扫码时卡劵详情
Route::rule('CouponListSelect/', 'CouponList/couponSelect');// 卡劵营销 查询卡劵
Route::rule('CouponListVip/', 'CouponList/couponVip');      // 卡劵营销 按照卡劵找未使用这张卡劵的人


/**
 * 生日折扣  
 */
Route::rule('webBirthdayDiscountIndex/', 'BirthdayDiscount/index');
Route::rule('webBirthday/', 'BirthdayDiscount/barthday'); // 生日有礼


/**
 * 客服中心    
 */
Route::rule('webCustomerLevel/', 'CustomerService/level');                          //会员等级
Route::rule('webCustomerServiceIndex/', 'CustomerService/index');                   //客服中心 登录人联系过的会员    
Route::rule('webCustomerServiceShu/', 'CustomerService/shu');                       //筛选会员
Route::rule('webCustomerServiceclickLabel/', 'CustomerService/clickLabel');         //点击条件加入缓存  
Route::rule('webCustomerPreservation/', 'CustomerService/preservation');            //保存条件
Route::rule('webCustomerScreenList/', 'CustomerService/screenList');                //筛选列表  
Route::rule('webCustomerScreenDel/', 'CustomerService/screenDel');                  //删除筛选条件
Route::rule('webCustomerCacheNull/', 'CustomerService/CacheNull');                  //清理缓存    


/**
 * 产品画像
 */
Route::rule('webCommodityScreeningIndex/', 'CommodityScreening/index');                     //筛选会员/会员列表
Route::rule('webCommodityScreeningLabel/', 'CommodityScreening/label');                     //商品标签
Route::rule('webCommodityScreeningLabelInfo/', 'CommodityScreening/labelInfo');             //商品标签 下一级
Route::rule('webCommodityScreeningCache/', 'CommodityScreening/cache');                     //点击加入到缓存
Route::rule('webCommodityScreeningPreservations/', 'CommodityScreening/preservations');     //保存筛选条件到筛选器
Route::rule('webCommodityScreeningScreenLists/', 'CommodityScreening/screenLists');         //筛选器列表
Route::rule('webCommodityScreeningColor/', 'CommodityScreening/color');                     //颜色
Route::rule('webCommodityScreeningSize/', 'CommodityScreening/size');                       //尺码


Route::rule('webCommoditySpecial/', 'CommodityScreening/commoditySpecial');         //产品画像保存专场根进模板
Route::rule('webVipSpecial/', 'CustomerService/vipSpecial');                       //顾客画像保存专场根进模板



/**
 * 商品列表
 */
Route::rule('webGoodsIndex/', 'Goods/index');                               // 列表

Route::rule('webGoodsYanse/', 'Goods/yanse');               //查询商品的颜色
Route::rule('webGoodsChima/', 'Goods/chima');               //查询商品的尺码
Route::rule('webGoodsGoodsAdd/', 'Goods/goodsAdd');         //添加商品

Route::rule('webGoodsLabelIndex/', 'Goods/labelIndex');                     // 会员标签列表
Route::rule('webGoodsLabel/', 'Goods/label');                               // 商品大标签
Route::rule('webGoodsLabelInfoq/', 'Goods/labelInfoq');                     // 小标签
Route::rule('webGoodsLabelInfo/', 'Goods/labelInfo');                       // 标签展示
Route::rule('webGoodsClickLabel/', 'Goods/clickLabel');                     // 点击标签加入到数据库

/**
 * 货客精准   
 */
Route::rule('webCargoGuest/', 'CargoGuest/index');                             // 列表
Route::rule('webCargoGuestUser/', 'CargoGuest/user');                          // 查找会员

/**
 * 畅销排行  
 */
Route::rule('webBestSeller/', 'BestSeller/index');                           // 列表
Route::rule('webBestSee/', 'BestSeller/see');                                // 查看

/**
 * 返单计划    
 */
Route::rule('webReturnOrder/', 'ReturnOrder/index');                           // 列表   
Route::rule('webReturnOrderAddCache/', 'ReturnOrder/addCache');                // 添加商品到缓存
Route::rule('webReturnClearCache/', 'ReturnOrder/ClearCache');                 // 删除全部商品的缓存   
Route::rule('webReturnOrderDelCache/', 'ReturnOrder/delCache');                // 删除商品的缓存 
Route::rule('webReturnOrderAddOrder/', 'ReturnOrder/addOrder');                // 新建返单
Route::rule('webReturnOrderSearch/', 'ReturnOrder/search');                    // 商品搜索
Route::rule('webReturnlikeVip/', 'ReturnOrder/likeVip');                       // 查询会员
Route::rule('webReturnSeeOrder/', 'ReturnOrder/SeeOrder');                     // 查看返单商品
Route::rule('webReturnAddOrderCache/', 'ReturnOrder/addOrderCache');           // 新建返单赋值商品用
Route::rule('webReturnEditOrder/', 'ReturnOrder/editOrder');                   // 编辑返单商品
Route::rule('webReturnSummary/', 'ReturnOrder/summary');                       // 返单总结



/**
 * 富信
 */
Route::rule('webSendWithMmsID/', 'Fuxin/SendWithMmsID');    //发送富信                
Route::rule('webimgcache/', 'Fuxin/imgcache');              //添加帧  
Route::rule('webimgdelCache/', 'Fuxin/delCache');           //删除帧 
Route::rule('webimgEmptyCache/', 'Fuxin/emptyCache');       //清除除全部帧 
Route::rule('webimgxiaoxi/', 'Fuxin/xiaoxi');               //消息提醒

/**
 * 微信
 */
Route::rule('webUserlistGroupSmg/', 'Weixin/groupSmg');             // 短信   
Route::rule('UserlistGroupSending/', 'Weixin/groupSending');        // 微信群送消息
Route::rule('UserlistWxChatRecord/', 'Weixin/wxChatRecord');        // 微信聊天记录
Route::rule('UserlistWxSendOutTxt/', 'Weixin/wxSendOutTxt');        // 微信发送文字消息
Route::rule('UserlistWxSendOutImage/', 'Weixin/wxSendOutImage');    // 微信发送图片消息
Route::rule('UserlistIssetOpenid/', 'Weixin/issetOpenid');          // 用openid查询会员信息
Route::rule('UserlistvipCache/', 'Weixin/tyingCard');               // 老会员绑卡 
Route::rule('Userlistopenid/', 'Weixin/openid');                    // 有openid时，用openid查询员工信息放入session


/**
 * 会员中心
 */
Route::rule('webViplistIntegralFlow/', 'Viplist/integralFlow');             //积分流水      
Route::rule('webViplistStoredValueFlow/', 'Viplist/storedValueFlow');       //储值流水    

Route::rule('webViplistChasys/', 'Viplist/chasys');                         //查询系统设置
Route::rule('webViplistChasysMoney/', 'Viplist/chasysMoney'); 

Route::rule('webViplistPointExchange/', 'Viplist/pointExchange');           //需要积分兑换的卡劵  
Route::rule('webViplistListIntegral/', 'Viplist/listIntegral');             //查询会员积分  
Route::rule('webViplistOffExchange/', 'Viplist/offExchange');               //兑换后扣除积分，增加卡劵  
Route::rule('webViplistVipStaff/', 'Viplist/vipStaff');                     //专属顾问

/**
 * V票  
 */
Route::rule('webVticket_reward/', 'Vticket/vticket_reward');        // 奖励V票 
Route::rule('webVticket_exchange/', 'Vticket/vticket_exchange');    // 兑换V票
Route::rule('webReward_record/', 'Vticket/reward_record');          // 奖励记录
Route::rule('webExchange_record/', 'Vticket/exchange_record');      // 兑换记录
Route::rule('webVnumber/', 'Vticket/vnumber');                      // 我的V票剩余数量
Route::rule('webVstaff/', 'Vticket/Vstaff');        

/**
 * 学文数据接口   
 */
Route::rule('webStoreInformation/', 'Viplist/storeInformation');        // 根据门店code查询门店信息   
Route::rule('webVipInformation/', 'Viplist/vipInformation');            // 根据openid查询会员信息  
Route::rule('webCoupons/', 'Viplist/Coupons');                          // 根据openid查询会员卡劵     
Route::rule('webObtainStaff/', 'Viplist/obtainStaff');                  // 通过员工code查openid
Route::rule('webConsumptionRecord/', 'Viplist/consumptionRecord');      // 消费接口 
Route::rule('webRecharge/', 'Viplist/recharge');                        // 充值接口  
Route::rule('webVipStore/', 'Viplist/vipStore');                        // 门店  
Route::rule('webVipStaffs/', 'Viplist/vipStaffs');                      // 员工 
Route::rule('vipIntroducer/', 'Viplist/vipIntroducer');                 // 查找介绍人
Route::rule('vipRegister/', 'Viplist/vipRegister');                     // 注册会员 



/**
 * 生日提醒  
 */
Route::rule('webBirRemind/', 'BirthdayDiscount/bir_remind');        // 查询生日人数
Route::rule('webBirSelect/', 'BirthdayDiscount/bir_select');        // 查询生日会员具体信息


/**
 * 复购流程   
 */
Route::rule('webRefreeIndex/', 'Strategy/refreeIndex');          // 免费策略                  // 根据openid查询会员卡劵   
Route::rule('webRevalueIndex/', 'Strategy/revalueIndex');        // 价值策略 
Route::rule('webRebelieveIndex/', 'Strategy/rebelieveIndex');    // 相信策略                    // 充值接口  


/**
 * 付费卡劵  
 */
Route::rule('webPayCoupon/', 'PayCoupon/index');                //付费卡劵    
Route::rule('webBirSelect/', 'PayCoupon/bir_select');       


/**
 * 微信支付   
 */
Route::rule('webVipQuery/', 'WxSell/vipQuery');                 //查询会员信息
Route::rule('webGoodsQuery/', 'WxSell/goodsQuery');             //查询商品信息
Route::rule('webStackableCoupon/', 'WxSell/StackableCoupon');   //查询可用优惠劵

Route::rule('webSalesAdd/', 'WxSell/salesAdd');                 //扫码付
Route::rule('webDirectPayment/', 'WxSell/directPayment');       //直接付   

Route::rule('webQueryCommodity/', 'WxSell/queryCommodity');    //查询商品信息
Route::rule('webSweepPayment/', 'WxSell/sweepPayment');       //扫码付 - 支付成功    
Route::rule('webSuccessful/', 'WxSell/successful');           //扫码付 - 员工端查询支付状态
Route::rule('webDeductionMoney/', 'WxSell/deductionMoney');   //查询会员储值，扣除储值

