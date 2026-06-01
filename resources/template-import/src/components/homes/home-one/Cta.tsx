import Image from "next/image"
import Link from "next/link"

import phone from "@/assets/img/cta/phone.png"
import icon_1 from "@/assets/img/cta/google.png"
import icon_2 from "@/assets/img/cta/app.png"

const Cta = () => {
   return (
      <div className="tg-cta-area tg-cta-su-wrapper tg-cta-space z-index-9 p-relative">
         <div className="container">
            <div className="row">
               <div className="col-12">
                  <div className="tg-cta-wrap include-bg" style={{ backgroundImage: `url(/assets/img/cta/banner.jpg)` }}>
                     <div className="row align-items-end">
                        <div className="col-lg-3 d-none d-lg-block">
                           <div className="tg-cta-thumb pt-50 ml-60">
                              <Image src={phone} alt="" />
                           </div>
                        </div>
                        <div className="col-lg-5 col-md-6">
                           <div className="tg-cta-content">
                              <h5 className="tg-section-subtitle text-white mb-10">Explore Tour</h5>
                              <h2 className="mb-15 tg-cta-title text-white text-none">Download Our App <br /> Form Google & App Store!</h2>
                           </div>
                        </div>
                        <div className="col-lg-4 col-md-6">
                           <div className="tg-cta-apps">
                              <Link className="mb-20 d-inline-block mr-5" href="#"><Image src={icon_1} alt="" /></Link>
                              <Link className="mb-20 d-inline-block" href="#"><Image src={icon_2} alt="" /></Link>
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
