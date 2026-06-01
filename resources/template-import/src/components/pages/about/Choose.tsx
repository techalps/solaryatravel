import Choose6 from "@/svg/home-one/Choose6";
import Choose7 from "@/svg/home-one/Choose7";
import Choose8 from "@/svg/home-one/Choose8";
import Image from "next/image";
import { JSX } from "react";

import shape from "@/assets/img/banner/banner-2/shape.png"

interface DataType {
   id: number;
   icon: JSX.Element;
   title: string;
   desc: string;
}

const choose_data: DataType[] = [
   {
      id: 1,
      icon: (<><Choose6 /></>),
      title: "Ultimate flexibility",
      desc: "when an unknown printer took galleof type and scrambled make type peci bookhas survived five.",
   },
   {
      id: 2,
      icon: (<><Choose7 /></>),
      title: "Memorable experiences",
      desc: "when an unknown printer took galleof type and scrambled make type peci bookhas survived five.",
   },
   {
      id: 3,
      icon: (<><Choose8 /></>),
      title: "Award winning support",
      desc: "when an unknown printer took galleof type and scrambled make type peci bookhas survived five.",
   },
];

const Choose = () => {
   return (
      <div className="tg-chose-area tg-grey-bg pt-140 pb-70 p-relative z-index-1">
         <Image className="tg-chose-6-shape d-none d-md-block" src={shape} alt="" />
         <div className="container">
            <div className="row justify-content-center">
               <div className="col-xl-6 col-lg-7 col-md-9">
                  <div className="tg-chose-section-title text-center mb-35">
                     <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".1s">What we do</h5>
                     <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">We Arrange the Best Tour<br /> Ever Possible</h2>
                     <p className="text-none wow fadeInUp mb-35" data-wow-delay=".5s" data-wow-duration=".9s"> when an unknown printer took a galley of type and scrambled make type
                        specimen bookhas survived not only five.</p>
                  </div>
               </div>
            </div>
            <div className="row">
               {choose_data.map((item) => (
                  <div key={item.id} className="col-lg-4 col-md-6">
                     <div className="tg-chose-6-wrap mb-30">
                        <span className="icon mb-20">{item.icon}</span>
                        <h4 className="tg-chose-6-title mb-15">{item.title}</h4>
                        <p>{item.desc}</p>
                     </div>
                  </div>
               ))}
            </div>
         </div>
      </div>
   )
}

export default Choose
