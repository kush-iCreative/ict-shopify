import { useState } from "react";
// Removed BlockStack, added VerticalStack (compatible with v11/v12) or use LegacyCard spacing
import { Page, Layout, LegacyCard, TextField, Button, TextContainer } from "@shopify/polaris";
import { useAuthenticatedFetch } from "../hooks";

export default function HomePage() {
  const fetch = useAuthenticatedFetch();
  const [settingValue, setSettingValue] = useState("");
  const [isSaving, setIsSaving] = useState(false);

  

const handleSave = async () => {
  setIsSaving(true); // Turn on loading spinner
  try {
   const cleanDomain = "aktv-2.myshopify.com"; 

    const response = await fetch("/api/settings", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        setting_value: settingValue,
        shop_domain: cleanDomain // NO https:// and NO trailing slash /
      }),
    });

    if (response.ok) {
      console.log("Saved successfully!");
    } else {
      const errorData = await response.json();
      console.error("Server Error:", errorData);
    }
  } catch (error) {
    console.error("Network Error:", error);
  } finally {
    setIsSaving(false); // Turn off loading spinner
  }
};



  return (
    <Page title="App Settings">
      <Layout>
        <Layout.Section>
          {/* LegacyCard is safe for older versions and avoids the deprecation warning */}
          <LegacyCard sectioned>
            <TextContainer>
              <TextField
                label="Custom Setting"
                value={settingValue}
                onChange={(value) => setSettingValue(value)}
                autoComplete="off"
                placeholder="Enter value to save..."
              />
              <Button primary loading={isSaving} onClick={handleSave}>
                Save Setting
              </Button>
            </TextContainer>
          </LegacyCard>
        </Layout.Section>
      </Layout>
    </Page>
  );
}
