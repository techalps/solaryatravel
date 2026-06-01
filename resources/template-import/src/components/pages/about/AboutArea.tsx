import Image from "next/image"
import Button from "@/components/common/Button"
import Link from "next/link"

import shape_1 from "@/assets/img/about/details/shape.png"
import shape_2 from "@/assets/img/about/details/shape-2.png"
import shape_3 from "@/assets/img/chose/chose-3/circle-text.png"
import shape_4 from "@/assets/img/chose/chose-3/star.png"
import thumb_1 from "@/assets/img/about/details/thumb-1.jpg"
import thumb_2 from "@/assets/img/about/details/thumb-3.jpg"
import thumb_3 from "@/assets/img/about/details/thumb-2.jpg"

const AboutArea = () => {
   return (
      <div className="tg-about-area p-relative z-index-1 pt-140 pb-105">
         <Image className="tg-about-details-shape p-absolute d-none d-lg-block" src={shape_1} alt="shape" />
         <div className="container">
            <div className="row align-items-center">
               <div className="col-lg-6">
                  <div className="tg-about-details-left p-relative mb-15">
                     <Image className="tg-about-details-map p-absolute" src={shape_2} alt="map" />
                     <div className="row">
                        <div className="col-lg-6 col-md-6 col-sm-6">
                           <div className="tg-about-details-thumb p-relative z-index-9">
                              <Image className="main-thumb tg-round-15 w-100 mb-20" src={thumb_1} alt="thumb" />
                              <Image className="main-thumb tg-round-15 w-100 mb-20" src={thumb_2} alt="thumb" />
                           </div>
                        </div>
                        <div className="col-lg-6 col-md-6 col-sm-6">
                           <div className="tg-about-details-thumb-2 p-relative">
                              <div className="tg-chose-3-rounded p-relative mb-30">
                                 <Image className="rotate-infinite-2" src={shape_3} alt="" />
                                 <Image className="tg-chose-3-star" src={shape_4} alt="" />
                              </div>
                              <Image className="w-100 tg-round-15" src={thumb_3} alt="chose" />
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div className="col-lg-6">
                  <div className="tg-chose-content mb-35 ml-60">
                     <div className="tg-chose-section-title mb-30">
                        <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".1s">Explore the world with us</h5>
                        <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">The perfect vacation<br /> come true with<br /> our Travel Agency</h2>
                        <p className="text-none wow fadeInUp mb-35" data-wow-delay=".5s" data-wow-duration=".9s">when an unknown printer took a galley of type and scrambled
                           it to make a type specimen book. It has survived not only five
                           centuries but also the leap into electronic typesetting<br />
                           remaining essentially unchanged.</p>
                        <div className="tg-chose-btn wow fadeInUp" data-wow-delay=".8s" data-wow-duration=".9s">
                           <Link href="/tour-details" className="tg-btn tg-btn-switch-animation">
                              <Button text="Book Your Room" />
                           </Link>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   )
}

export default AboutArea
