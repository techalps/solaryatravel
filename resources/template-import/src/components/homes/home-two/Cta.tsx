import Image from "next/image"
import Link from "next/link"

import cta_1 from "@/assets/img/cta/su/shape-3.png"
import icon_1 from "@/assets/img/cta/google.png"
import icon_2 from "@/assets/img/cta/app.png"
import cta_thumb from "@/assets/img/cta/su/thumb.png"
import shape from "@/assets/img/cta/su/shape.png"

const Cta = () => {
   return (
      <div className="tg-cta-area-area tg-cta-su-2 tg-primary-bg p-relative z-index-1">
         <Image className="shape-2 p-absolute d-none d-xl-block" src={cta_1} alt="" />
         <div className="container">
            <div className="row align-items-center">
               <div className="col-lg-6 col-md-6">
                  <div className="tg-cta-3-content tg-process-content pt-50 pb-50">
                     <h5 className="tg-section-su-subtitle su-subtitle-2 mb-15">our Working Process</h5>
                     <h2 className="tg-section-su-title text-none mb-25">Get 5% off on your first <br />
                        app booking Apps</h2>
                     <div className="tg-cta-3-apps d-flex align-items-center">
                        <Link className="d-inline-block mr-10" href="#"><Image src={icon_1} alt="" /></Link>
                        <Link className="d-inline-block" href="#"><Image src={icon_2} alt="" /></Link>
                     </div>
                  </div>
               </div>
               <div className="col-lg-6 col-md-6">
                  <div className="tg-cta-3-thumb p-relative z-index-1 text-end pt-90">
                     <Image src={cta_thumb} alt="" />
                     <Image className="shape p-absolute d-none d-xl-block" src={shape} alt="" />
                  </div>
               </div>
            </div>
         </div>
      </div>
   )
}

export default Cta
