import ShopDetailsForm from "@/components/forms/ShopDetailsForm"
import Image from "next/image"

import avatar_1 from "@/assets/img/tour-details/avatar.png"
import avatar_2 from "@/assets/img/tour-details/avatr.png"

const Review = () => {
   return (
      <div className="tg-product-details-review">
         <div className="row">
            <div className="col-xl-9">
               <div className="tg-product-details-inner">
                  <div className="tg-tour-about-review-wrap mb-45">
                     <h4 className="tg-tour-about-title mb-15">Customer Reviews</h4>
                     <p className="text-none lh-28 mb-20">Castle in one day is next to impossible. Designed specifically for trave arelimited time in London, this tour
                        ws you to check off a range of southern England‘s are historical</p>
                  </div>
                  <div className="tg-tour-about-border mb-35"></div>
                  <div className="tg-tour-about-cus-review-wrap mb-25">
                     <h4 className="tg-tour-about-title mb-40">2 Reviews</h4>
                     <ul>
                        <li>
                           <div className="tg-tour-about-cus-review d-flex mb-40">
                              <div className="tg-tour-about-cus-review-thumb">
                                 <Image src={avatar_1} alt="avatar" />
                              </div>
                              <div>
                                 <div className="tg-tour-about-cus-name mb-5 d-flex align-items-center justify-content-between flex-wrap">
                                    <h6 className="mr-10 mb-10 d-inline-block">Ronald Richards <span>- 20 Mar, 2023 . 4:00 Pm</span></h6>
                                    <span className="tg-tour-about-cus-review-star mb-10 d-inline-block">
                                       <i className="fa-sharp fa-solid fa-star"></i>
                                       <i className="fa-sharp fa-solid fa-star"></i>
                                       <i className="fa-sharp fa-solid fa-star"></i>
                                       <i className="fa-sharp fa-solid fa-star"></i>
                                       <i className="fa-sharp fa-solid fa-star"></i>
                                    </span>
                                 </div>
                                 <p className="text-none lh-28 mb-10">Castle in one day is next to impossible. Designed specifically for trave areli areafol
                                    time in London, this tour allou to check off a range of southern  day is next together
                                    impossible. Designed speciEngland.</p>
                                 <a className="tg-tour-about-cus-reply" href="#">Reply</a>
                              </div>
                           </div>
                           <div className="tg-tour-about-border mb-40"></div>
                        </li>
                        <li>
                           <div className="tg-tour-about-cus-review d-flex mb-40">
                              <div className="tg-tour-about-cus-review-thumb">
                                 <Image src={avatar_2} alt="avatar" />
                              </div>
                              <div>
                                 <div className="tg-tour-about-cus-name mb-5 d-flex align-items-center justify-content-between flex-wrap">
                                    <h6 className="mr-10 mb-10 d-inline-block">Annette Black <span>- 20 Mar, 2023 . 4:00 Pm</span></h6>
                                    <span className="tg-tour-about-cus-review-star mb-10 d-inline-block">
                                       <i className="fa-sharp fa-solid fa-star"></i>
                                       <i className="fa-sharp fa-solid fa-star"></i>
                                       <i className="fa-sharp fa-solid fa-star"></i>
                                       <i className="fa-sharp fa-solid fa-star"></i>
                                       <i className="fa-sharp fa-solid fa-star"></i>
                                    </span>
                                 </div>
                                 <p className="text-none lh-28 mb-10">Castle in one day is next to impossible. Designed specifically for trave areli areafol
                                    time in London, this tour allou to check off a range of southern  day is next together
                                    impossible. Designed speciEngland.</p>
                                 <a className="tg-tour-about-cus-reply" href="#">Reply</a>
                              </div>
                           </div>
                        </li>
                     </ul>
                  </div>
                  <div className="tg-tour-about-border mb-45"></div>
                  <div className="tg-tour-about-review-form-wrap">
                     <h4 className="tg-tour-about-title mb-15">Leave a Reply</h4>
                     <div className="tg-tour-about-rating-category mb-20">
                        <ul>
                           <li>
                              <label>Ratings :</label>
                              <div className="rating-icon">
                                 <i className="fa-sharp fa-solid fa-star"></i>
                                 <i className="fa-sharp fa-solid fa-star"></i>
                                 <i className="fa-sharp fa-solid fa-star"></i>
                                 <i className="fa-sharp fa-solid fa-star"></i>
                                 <i className="fa-sharp fa-solid fa-star"></i>
                              </div>
                           </li>
                        </ul>
                     </div>
                     <div className="tg-tour-about-review-form">
                        <ShopDetailsForm />
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   )
}

export default Review
