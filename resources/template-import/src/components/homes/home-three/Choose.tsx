import Image from "next/image"
import Choose4 from "@/svg/home-one/Choose4"
import Choose5 from "@/svg/home-one/Choose5"
import Button from "@/components/common/Button"

import choose_shape from "@/assets/img/chose/chose-shape-2.png"
import choose_shape2 from "@/assets/img/chose/chose-shape.png"
import choose_thumb from "@/assets/img/chose/chose.png"
import choose_thumb2 from "@/assets/img/chose/chose-2.jpg"
import Link from "next/link"


const Choose = () => {
   return (
      <div className="tg-chose-area p-relative pt-135 pb-100">
         <Image className="tg-chose-shape p-absolute" src={choose_shape} alt="shape" />
         <div className="container">
            <div className="row">
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
                           <Link href="/contact" className="tg-btn tg-btn-switch-animation">
                              <Button text="Book Your Trip" />
                           </Link>
                        </div>
                     </div>
                  </div>
               </div>

               <div className="col-lg-7">
                  <div className="tg-chose-right mb-25">
                     <div className="row">
                        <div className="col-lg-3 col-md-6">
                           <div className="tg-chose-thumb">
                              <Image className="tg-chose-shape-2 mb-30 ml-15 d-none d-lg-block" src={choose_shape2} alt="shape" />
                              <Image className="w-100 wow fadeInRight" data-wow-delay=".4s" data-wow-duration=".9s" src={choose_thumb} alt="chose" />
                           </div>
                        </div>
                        <div className="col-lg-9 col-md-6">
                           <div className="tg-chose-thumb-inner p-relative">
                              <div className="tg-chose-thumb-2 wow fadeInRight" data-wow-delay=".5s" data-wow-duration=".9s">
                                 <Image className="w-100 tg-round-15" src={choose_thumb2} alt="chose" />
                              </div>
                              <div className="tg-chose-big-text d-none d-xl-block">
                                 <h2 data-text="TRAVEL">TRAVEL</h2>
                              </div>
                           </div>
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
