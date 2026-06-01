import Image from "next/image"
import Link from "next/link"

import review_1 from "@/assets/img/tour-details/avatar.png"
import review_2 from "@/assets/img/tour-details/avatr.png"

const ReviewDetails = () => {
   return (
      <div className="tg-tour-about-cus-review-wrap mb-25">
         <h4 className="tg-tour-about-title mb-40">2 Reviews</h4>
         <ul>
            <li>
               <div className="tg-tour-about-cus-review d-flex mb-40">
                  <div className="tg-tour-about-cus-review-thumb">
                     <Image src={review_1} alt="avatar" />
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
                     <Link className="tg-tour-about-cus-reply" href="#">Reply</Link>
                  </div>
               </div>
               <div className="tg-tour-about-border mb-40"></div>
            </li>
            <li>
               <div className="tg-tour-about-cus-review d-flex mb-40">
                  <div className="tg-tour-about-cus-review-thumb">
                     <Image src={review_2} alt="avatar" />
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
                     <Link className="tg-tour-about-cus-reply" href="#">Reply</Link>
                  </div>
               </div>
            </li>
         </ul>
      </div>
   )
}

export default ReviewDetails
