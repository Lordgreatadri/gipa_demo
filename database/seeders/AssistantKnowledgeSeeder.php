<?php

namespace Database\Seeders;

use App\Models\AssistantDocument;
use App\Services\Assistant\KnowledgeIndexer;
use Illuminate\Database\Seeder;

class AssistantKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->documents() as $document) {
            AssistantDocument::updateOrCreate(
                ['slug' => $document['slug']],
                [
                    'title' => $document['title'],
                    'category' => $document['category'],
                    'summary' => $document['summary'],
                    'body' => $document['body'],
                    'source_type' => 'seed',
                    'is_published' => true,
                ],
            );
        }

        app(KnowledgeIndexer::class)->reindexAll(force: true);
    }

    /**
     * @return array<int, array{slug: string, title: string, category: string, summary: string, body: string}>
     */
    private function documents(): array
    {
        return [
            [
                'slug' => 'about-the-platform',
                'title' => 'About the Investment Opportunities Mapping Platform',
                'category' => 'about',
                'summary' => 'What the platform is and who it serves.',
                'body' => "The Investment Opportunities Mapping Platform (IOMP) is an official platform of the Ghana Investment Promotion Authority (GIPA). It maps verified, investment-ready opportunities across the districts and regions of Ghana so that local and international investors can discover, evaluate and pursue projects with confidence.\n\n"
                    ."The platform brings together three things in one place: a searchable registry of published investment opportunities, district and regional investment profiles with an interactive map, and a secure investor onboarding and matchmaking workflow. Every opportunity shown publicly has been reviewed and approved through the platform's governance workflow before it is published.\n\n"
                    .'GIPA is the government agency responsible for encouraging, promoting and facilitating investment in Ghana. The platform is one of the tools GIPA uses to make investment information transparent and accessible.',
            ],
            [
                'slug' => 'how-to-find-opportunities',
                'title' => 'How to find and evaluate investment opportunities',
                'category' => 'opportunities',
                'summary' => 'Finding, filtering and reviewing opportunities.',
                'body' => "You can browse every published investment opportunity from the Opportunities page. Each opportunity belongs to a sector and a district, and includes an overview, objectives, location details and, where available, financial information such as the estimated investment amount and expected returns.\n\n"
                    ."Use the filters to narrow opportunities by sector, region or district, and by status. Opportunities are only shown publicly once they have been approved and published, and once their district is published, so what you see is verified information.\n\n"
                    .'When an opportunity interests you, you can submit an inquiry directly from its page. If you are a registered investor with an approved profile, the platform can also match opportunities to your investment mandate automatically.',
            ],
            [
                'slug' => 'investor-onboarding-kyc',
                'title' => 'Becoming an investor: registration and KYC',
                'category' => 'onboarding',
                'summary' => 'Steps and documents to onboard as an investor.',
                'body' => "To take part as an investor you create an investor account, verify your email address, and complete your investor profile and investment mandate. You then submit your Know Your Customer (KYC) documents for review by GIPA officers.\n\n"
                    ."KYC is a compliance step that confirms your identity and, for organisations, your legal standing. The exact documents required depend on whether you register as an individual or an organisation, and the platform lists the required and supporting documents during onboarding. Typical documents include proof of identity and, for companies, incorporation and ownership documents.\n\n"
                    ."Once your documents are reviewed and your profile is approved, you can express interest in opportunities, receive tailored opportunity matches, and be contacted by district investment teams.\n\n"
                    .'You can start the process from the investor registration page. Your onboarding progress and any requests for additional documents appear in your investor workspace.',
            ],
            [
                'slug' => 'certificate-verification',
                'title' => 'Verifying a GIPA certificate',
                'category' => 'certificates',
                'summary' => 'How to confirm a certificate is authentic.',
                'body' => "Certificates issued through the platform are digitally signed and tamper-evident. Every certificate carries a unique QR code and a verification link.\n\n"
                    ."To confirm that a certificate is genuine, scan the QR code or open the verification link printed on the certificate. It resolves to the official platform and displays the certificate's current status (for example active, suspended or revoked), the holder, the certificate type, and the issue and expiry dates.\n\n"
                    .'If a certificate cannot be found or its status is not active, treat it with caution and contact GIPA to confirm. Never rely on a photocopy alone — always verify through the QR code or verification link.',
            ],
            [
                'slug' => 'districts-and-regions',
                'title' => 'Districts, regions and the investment map',
                'category' => 'districts',
                'summary' => 'How district and regional data is presented.',
                'body' => "The platform profiles districts across the regions of Ghana. Each published district includes its capital, a location description, an investment readiness score and economic context, and the investment opportunities located within it.\n\n"
                    ."The interactive investment map lets you explore opportunities geographically and compare districts. Readiness scores help investors gauge how prepared a district is to host and support investment.\n\n"
                    .'Only districts that have completed review and been published appear publicly, so the profiles reflect verified information.',
            ],
            [
                'slug' => 'sectors-and-industries',
                'title' => 'Investment sectors on the platform',
                'category' => 'sectors',
                'summary' => 'How opportunities are classified by sector.',
                'body' => "Every investment opportunity is classified under a sector, and often a more specific sub-sector, so investors can focus on the industries that match their interests. Sectors reflect the priority areas GIPA promotes for investment in Ghana.\n\n"
                    .'You can filter opportunities by sector on the Opportunities page. Ask the assistant to list the current active sectors and it will read them directly from the live platform data.',
            ],
            [
                'slug' => 'contacting-gipa',
                'title' => 'Contacting GIPA and getting support',
                'category' => 'contacts',
                'summary' => 'How to reach the authority for help.',
                'body' => "For questions that go beyond the published information on the platform — such as specific incentives, legal facilitation, or the status of an application — you should contact the Ghana Investment Promotion Authority directly.\n\n"
                    ."GIPA facilitates investor registration, provides aftercare, and helps investors navigate government approvals. The platform's public pages carry the authority's official contact details in the site footer, and registered investors can raise requests from within their investor workspace.\n\n"
                    .'The assistant does not provide personal legal, tax or financial advice; for those, please consult GIPA or a qualified professional.',
            ],
            [
                'slug' => 'general-faq',
                'title' => 'Frequently asked questions',
                'category' => 'faq',
                'summary' => 'Common questions about using the platform.',
                'body' => "Is the platform free to browse? Yes. Anyone can browse published opportunities, districts and sectors without an account.\n\n"
                    ."Do I need an account to invest? To express interest formally, receive matches and be contacted, you register as an investor and complete KYC.\n\n"
                    ."Are the opportunities verified? Yes. Opportunities are reviewed and approved through a governance workflow before they are published.\n\n"
                    ."How do I check a certificate is real? Scan the QR code or open the verification link on the certificate to see its live status.\n\n"
                    .'Who runs the platform? It is an official platform of the Ghana Investment Promotion Authority (GIPA).',
            ],
        ];
    }
}
