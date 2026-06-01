import Image from "next/image"
import Link from "next/link"

import img_1 from "@/assets/img/cta/cta-3/shape-2.png"
import img_2 from "@/assets/img/cta/cta-4/thumb.png"
import img_3 from "@/assets/img/cta/cta-3/shape.png"
import icon_1 from "@/assets/img/cta/google.png"
import icon_2 from "@/assets/img/cta/app.png"

const Cta = () => {
   return (
      <div className="tg-cta-area-area">
         <div className="container">
            <div className="row align-items-center">
               <div className="col-12">
                  <div className="tg-cta-4-spacing tg-primary-bg p-relative z-index-1">
                     <Image className="tg-cta-3-shape-2 d-none d-md-block" src={img_1} alt="" />
                     <div className="row align-items-center">
                        <div className="col-lg-6 col-md-6">
                           <div className="tg-cta-4-thumb mr-25 p-relative z-index-1  pt-85 ">
                              <Image className="w-100" src={img_2} alt="" />
                              <Image className="tg-cta-3-shape rotate-infinite" src={img_3} alt="shape" />
                           </div>
                        </div>
                        <div className="col-lg-6 col-md-6">
                           <div className="tg-cta-4-content mb-20 pt-15">
                              <h2 className="mb-15 tg-cta-title text-white text-none mb-15">Get 5% off on your First app<br /> booking Travel with us</h2>
                              <p className="text-white">Are you tired of the typical tourist destinations and looking
                                 comfort zonetravelAre you tired of the typical tourist destin
                                 to step out of your comfort zonetravel</p>
                              <div className="tg-cta-3-apps pt-15 d-flex align-items-center">
                                 <Link className="mb-10 d-inline-block mr-10" href="#"><Image src={icon_1} alt="" /></Link>
                                 <Link className="mb-10 d-inline-block" href="#"><Image src={icon_2} alt="" /></Link>
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

export default Cta
