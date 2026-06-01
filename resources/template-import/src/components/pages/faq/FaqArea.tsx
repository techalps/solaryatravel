"use client"
import { useEffect, useState } from "react";
import faq_data from "@/data/FaqData"

interface FaqData {
   id: number;
   title: string;
   desc: string;
   showAnswer: boolean;
};

const FaqArea = () => {

   const [faqData, setFaqData] = useState<FaqData[]>([]);

   useEffect(() => {
      const filtered = faq_data;
      const updatedData = faq_data.map((item) => ({
         ...item,
         showAnswer: item.id === filtered[1]?.id
      }));
      setFaqData(updatedData);
   }, []);

   const toggleAnswer = (faqId: number) => {
      setFaqData((prevFaqData) =>
         prevFaqData.map((faq) => ({
            ...faq,
            showAnswer: faq.id === faqId
         }))
      );
   };

   return (
      <div className="tg-pricing-area pb-120 pt-125 p-relative">
         <div className="container">
            <div className="row justify-content-center">
               <div className="col-lg-10">
                  <div className="tg-faq-content-wrap">
                     <div className="tg-faq-section-title text-center mb-40">
                        <h5 className="tg-section-subtitle mb-15 wow fadeInUp" data-wow-delay=".3s" data-wow-duration=".9s">Have questions you want answers to?</h5>
                        <h2 className="mb-15 text-none wow fadeInUp" data-wow-delay=".4s" data-wow-duration=".9s">frequently Ask Questions</h2>
                     </div>
                     <div className="tg-faq-content">
                        <div className="accordion tg-custom-accordion" id="accordionExample">
                           {faqData.map((item) => (
                              <div key={item.id} className={`accordion-item ${item.showAnswer ? "tg-faq-active" : ""} mb-10 wow fadeInUp`} data-wow-delay=".3s" data-wow-duration=".9s">
                                 <h2 className="accordion-header" onClick={() => toggleAnswer(item.id)}>
                                    <button className="accordion-button" type="button">
                                       {item.title}
                                    </button>
                                 </h2>
                                 <div id="collapseOne" className={`accordion-collapse collapse ${item.showAnswer ? "show" : ""}`}>
                                    <div className="accordion-body">
                                       <p className="mb-0">{item.desc}</p>
                                    </div>
                                 </div>
                              </div>
                           ))}
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   )
}

export default FaqArea
