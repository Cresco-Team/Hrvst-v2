declare namespace App {
namespace DTOs {
namespace Product {
export type VarietyAnalyticsDTO = {
supply_demand_ratio: number,
imbalance_band: App.Enums.Analytics.ImbalanceBand,
supply_fulfillment_rate: number | null,
demand_fulfillment_rate: number | null,
supply_volume_mom_pct: number | null,
demand_volume_mom_pct: number | null,
recommendations: App.DTOs.Product.VarietyRecommendationDTO[],
forecast: {
month: string,
label: string,
supply_kg: number,
demand_kg: number,
}[],
};
export type VarietyRecommendationDTO = {
readonly severity: App.Enums.Analytics.RecommendationSeverity,
readonly type: string,
readonly title: string,
readonly body: string,
};
}
}
namespace Data {
namespace Category {
export type CategoryData = {
id: number,
name: string,
slug: string,
};
}
namespace Dealer {
export type DealerExpiringDemandData = {
id: number,
scheduled_date: string | null,
time_slot: App.Enums.PostTimeSlot | null,
time_slot_label: string | null,
created_at: string,
created_at_human: string,
image_url: undefined | string,
items: App.Data.PostItem.PostItemLightData[] | undefined,
};
}
namespace Farmer {
export type FarmerExpiringSupplyData = {
id: number,
scheduled_date: string | null,
time_slot: App.Enums.PostTimeSlot | null,
time_slot_label: string | null,
created_at: string,
created_at_human: string,
image_url: undefined | string,
items: App.Data.PostItem.PostItemLightData[] | undefined,
};
}
namespace Post {
export type DealerDemandData = {
id: number,
user_id: number,
type: App.Enums.PostType,
scheduled_date: string,
time_slot: App.Enums.PostTimeSlot,
created_at: string,
created_at_human: string,
post_items: App.Data.PostItem.PostItemLightData[] | unknown,
};
export type FarmerSupplyData = {
id: number,
user_id: number,
type: App.Enums.PostType,
scheduled_date: string,
time_slot: App.Enums.PostTimeSlot,
created_at: string,
created_at_human: string,
post_items: App.Data.PostItem.PostItemLightData[],
};
export type PostData = {
id: number,
type: App.Enums.PostType,
scheduled_date: string | null,
time_slot: string | null,
time_slot_label: string | null,
days_until_transaction: number | null,
created_at: string,
created_at_human: string,
image_url: undefined | string,
items: App.Data.PostItem.PostItemData[] | undefined,
};
}
namespace PostItem {
export type PostItemData = {
id: number,
image_url: string,
name: string,
post_id: number,
status: App.Enums.PostItemStatus,
vegetable_id: number,
variety_name: string,
vegetable_name: string,
category_name: string,
quantity_kg: number,
scheduled_date: string | null,
time_slot: string | null,
time_slot_label: string | null,
days_until_transaction: number | null,
created_at: string,
created_at_human: string,
};
export type PostItemLightData = {
id: number,
vegetable_id: number,
variety_name: string | null,
vegetable_name: string | null,
display_name: string | null,
vegetable_image_url: string | null,
quantity_kg: number,
status: App.Enums.PostItemStatus | null,
};
}
namespace Profile {
export type CoordinatesData = {
lat: number,
lng: number,
};
export type DealerData = {
id: number,
joined_at: string,
joined_at_human: string,
user: App.Data.Profile.UserData | undefined,
ongoing_demands_count: undefined | number,
demands: App.Data.PostItem.PostItemLightData[] | undefined,
demand_items: App.Data.PostItem.PostItemData[] | undefined,
};
export type FarmerData = {
id: number,
joined_at: string,
joined_at_human: string,
user: App.Data.Profile.UserData | undefined,
full_address: string,
barangay: undefined | string | null,
municipality: undefined | string | null,
province: undefined | string | null,
coordinates: App.Data.Profile.CoordinatesData,
ongoing_supplies_count: undefined | number,
supplies: undefined | Array<any>,
supply_items: App.Data.PostItem.PostItemData[] | undefined,
};
export type UserData = {
id: number,
name: string,
email: string | null,
phone_number: string,
avatar_url: string,
};
}
namespace Vegetable {
export type VegetableAdminData = {
id: number,
vegetable_name: string,
variety_name: string | null,
local_name: string | null,
display_name: string,
image_url: string,
category: App.Data.Category.CategoryData | undefined,
supply_count: undefined | number,
demand_count: undefined | number,
};
export type VegetableData = {
id: number,
vegetable_name: string,
variety_name: string | null,
local_name: string | null,
display_name: string | null,
image_url: string,
category: App.Data.Category.CategoryData | undefined,
};
export type VegetableDetailData = {
id: number,
vegetable_id: number,
vegetable_name: string,
variety_name: string | null,
display_name: string,
category_id: number,
supply_count: number,
demand_count: number,
supply_municipalities: Array<any>,
monthly_activity: Array<any>,
variety_calendar: Array<any>,
analytics: Array<any> | null,
};
export type VegetableLightData = {
id: number,
name: string,
category: string | null,
image_url: string | null,
};
export type VegetableSharedData = {
id: number,
vegetable_name: string,
variety_name: string | null,
local_name: string | null,
display_name: string,
image_url: string,
category: App.Data.Category.CategoryData | undefined,
supply_count: undefined | number,
demand_count: undefined | number,
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
