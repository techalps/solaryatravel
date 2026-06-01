import Process4 from "@/svg/home-one/Process4";
import Process5 from "@/svg/home-one/Process5";
import Process6 from "@/svg/home-one/Process6";
import { JSX } from "react";

interface DataType {
   id: number;
   icon: JSX.Element;
   title: string;
   desc: string;
}

const process_data: DataType[] = [
   {
      id: 1,
      icon: (<><Process4 /></>),
      title: "Find Your Travel Destination",
      desc: "Do you want to relax on the beach, explore a new city, or go on an adventure?"
   },
   {
      id: 2,
      icon: (<><Process5 /></>),
      title: "Book Your Ticket",
      desc: "Do you want to relax on the beach, explore a new city, or go on an adventure?"
   },
   {
      id: 3,
      icon: (<><Process6 /></>),
      title: "Pay & Go Enjoy",
      desc: "Do you want to relax on the beach, explore a new city, or go on an adventure?"
   },
];

const Process = () => {
   return (
      <div className="tp-process-area include-bg pb-90 pt-120" style={{ backgroundImage: `url(/assets/img/process/bg.jpg)` }}>
         <div className="container">
            <div className="row align-items-center">
               <div className="col-lg-6">
                  <div className="tg-process-content mb-30 wow fadeInLeft" data-wow-delay=".4s" data-wow-duration=".9s">
                     <h5 className="tg-section-su-subtitle su-subtitle-2 mb-15">our Working Process</h5>
                     <h2 className="tg-section-su-title text-none mb-15">Book tickets easily with <br />
                        just a few steps</h2>
                     <p className="tg-section-su-para tg-section-su-para-2 mb-25">Are you tired of the typical tourist destinations and<br />
                        looking to step out of your</p>
                     <a href="contact.html" className="tg-btn tg-btn-transparent">Read More</a>
                  </div>
               </div>
               <div className="col-lg-6">
                  <div className="tg-process-list mb-10 wow fadeInRight" data-wow-delay=".4s" data-wow-duration=".9s">
                     {process_data.map((item) => (
                        <div key={item.id} className="tg-chose-list d-flex mb-20">
                           <span className="tg-chose-list-icon mr-20">
                              {item.icon}
                           </span>
                           <div className="tg-chose-list-content">
                              <h4 className="tg-chose-list-title mb-5">{item.title}</h4>
                              <p>{item.desc}</p>
                           </div>
                        </div>
                     ))}
                  </div>
               </div>
            </div>
         </div>
      </div>
   )
}

export default Process
