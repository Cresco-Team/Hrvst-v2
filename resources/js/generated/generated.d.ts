declare namespace App {
namespace Data {
namespace Post {
export type DealerDemandData = {
id: number,
user_id: number,
type: App.Enums.PostType,
scheduled_date: string,
time_slot: App.Enums.PostTimeSlot,
created_at: string,
created_at_human: string,
post_items: undefined<number, App.Data.PostItem.PostItemLightData>,
};
export type FarmerSupplyData = {
id: number,
user_id: number,
type: App.Enums.PostType,
scheduled_date: string,
time_slot: App.Enums.PostTimeSlot,
created_at: string,
created_at_human: string,
post_items: undefined<number, App.Data.PostItem.PostItemLightData>,
};
}
namespace PostItem {
export type PostItemLightData = {
id: number,
variety_id: number,
variety_name: string | null,
vegetable_name: string | null,
vegetable_image_url: string | null,
quantity_kg: number,
status: App.Enums.PostItemStatus | null,
};
}
namespace Variety {
export type VarietyLightData = {
id: number,
name: string,
};
}
namespace Vegetable {
export type VegetableLightData = {
id: number,
name: string,
category: string | null,
image_url: string | null,
};
}
}
namespace Enums {
export type PostItemStatus = 'ongoing' | 'fulfilled' | 'expired';
export type PostTimeSlot = 'morning' | 'afternoon' | 'evening';
export type PostType = 'supply' | 'demand';
namespace Analytics {
export type ImbalanceBand = 'oversupply' | 'balanced' | 'undersupply';
export type RecommendationSeverity = 'critical' | 'warning' | 'info';
export type VarietyViewerRole = 'admin' | 'marketplace';
}
}
}
