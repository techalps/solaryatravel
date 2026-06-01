import Image from "next/image"
import Button from "@/components/common/Button"
import Choose5 from "@/svg/home-one/Choose5"
import Choose4 from "@/svg/home-one/Choose4"

import img_1 from "@/assets/img/chose/chose-3/bridge.png"
import img_2 from "@/assets/img/chose/chose-3/map.png"
import img_3 from "@/assets/img/chose/chose-3/circle-text.png"
import img_4 from "@/assets/img/chose/chose-3/star.png"
import img_5 from "@/assets/img/chose/chose-3/thumb-2.jpg"
import img_6 from "@/assets/img/chose/chose-3/thumb-1.jpg"

const Choose = () => {
   return (
      <div className="tg-chose-area p-relative z-index-1  tg-grey-bg pt-115 pb-90">
         <Image className="tg-chose-3-shape p-absolute" src={img_1} alt="shape" />
         <div className="container">
            <div className="row">
               <div className="col-lg-7">
                  <div className="tg-chose-3-left p-relative mb-35">
                     <Image className="tg-chose-3-map" src={img_2} alt="map" />
                     <div className="row">
                        <div className="col-lg-4 col-md-6 col-sm-6">
                           <div className="tg-chose-3-thumb">
                              <div className="tg-chose-3-rounded p-relative mb-20">
                                 <Image className="rotate-infinite-2" src={img_3} alt="shape" />
                                 <Image className="tg-chose-3-star" src={img_4} alt="shape" />
                              </div>
                              <Image className="main-thumb" src={img_5} alt="" />
                           </div>
                        </div>
                        <div className="col-lg-8 col-md-6 col-sm-6">
                           <div className="tg-chose-3-thumb-inner p-relative">
                              <div className="tg-chose-3-thumb-2">
                                 <Image className="w-100 tg-round-15" src={img_6} alt="" />
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div className="col-lg-5">
                  <div className="tg-chose-content mb-25">
                     <div className="tg-chose-section-title mb-30">
                        <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".1s">Dream Your Next Trip</h5>
                        <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">discover when even<br /> you want to go</h2>
                        <p className="text-none wow fadeInUp" data-wow-delay=".5s" data-wow-duration=".9s">Are you tired of the typical tourist destinations and looking
                           to step out of your comfort zone? Adventure travel may be
                           the perfect solution for you! Here are four.</p>
                     </div>
                     <div className="tg-chose-list-wrap">
                        <div className="tg-chose-list d-flex mb-15 wow fadeInUp" data-wow-delay=".6s" data-wow-duration=".9s">
                           <span className="tg-chose-list-icon mr-20">
                              <Choose4 />
                           </span>
                           <div className="tg-chose-list-content">
                              <h4 className="tg-chose-list-title mb-5">Best Travel Agency</h4>
                              <p>Are you tired of the typical tourist destinatio
                                 and looking  step out of your comfort.</p>
                           </div>
                        </div>
                        <div className="tg-chose-list d-flex mb-40 wow fadeInUp" data-wow-delay=".7s" data-wow-duration=".9s">
                           <span className="tg-chose-list-icon mr-20">
                              <Choose5 />
                           </span>
                           <div className="tg-chose-list-content">
                              <h4 className="tg-chose-list-title mb-5">Secure Journey With Us</h4>
                              <p>Are you tired of the typical tourist destinatio
                                 and looking  step out of your comfort.</p>
                           </div>
                        </div>
                        <div className="tg-chose-btn wow fadeInUp" data-wow-delay=".8s" data-wow-duration=".9s">
                           <a href="contact.html" className="tg-btn tg-btn-switch-animation">
                              <Button text="Book Your Trip" />
                           </a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   )
}

export default Choose
