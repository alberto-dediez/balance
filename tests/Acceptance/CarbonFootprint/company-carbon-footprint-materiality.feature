Feature:
    I want the materiality verdicts to pre-exclude or activate the scope 3 categories of a company carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given company "Hotelera" with id "C1" of account "666" exists
        Given property "Franquicia" with id "2222" of account "666" exists
        Given property "2222" has company "C1" assigned
        Given property "2222" has operating regime "franchised"

    Scenario: Pre-excluding the franchises removes their non consolidated properties
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with materiality answered exists
        Given company carbon footprint "999" has a non consolidated property "2222" of source "franchises"
        Given materiality decisions for "company" carbon footprint "999":
            | topicNumber | score | threshold | forced | verdict   |
            | 14          | 10    | 50        | no     | omit      |
            | 13          | 70    | 50        | no     | calculate |
        Then "company" carbon footprint "999" should have source "franchises" excluded in scope "scope_3" with reason "not_significant"
        Then "company" carbon footprint "999" should not have source "downstream_leased_assets" excluded in scope "scope_3"
        Then company carbon footprint "999" should have 0 non consolidated properties
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/scope-3"
        Then the response status should be 200
        Then I see a text "No material · 10,00 % &lt; 50 %"
        Then I see a text "Material · 70,00 %"

    Scenario: The materiality step summary shows the real state of the company only categories
        Given standard materiality topic "13" with id "T13" exists
        Given standard materiality topic "14" with id "T14" exists
        Given materiality question "¿Qué volumen de emisiones representa?" with id "Q13" for criterion "influence" and topic "T13" exists
        Given materiality question "¿Qué volumen de emisiones representan?" with id "Q14" for criterion "influence" and topic "T14" exists
        Given company carbon footprint with id "999" for company "C1" of account "666" and year "2025" with scope 2 answered exists
        Given materiality assessment with id "A1" for "company" carbon footprint "999" of account "666" exists
        Given materiality assessment "A1" has all questions answered with value 1
        When I am logged as "owner@dediez.software"
        When I go to "/es/company-carbon-footprints/999/step/materiality"
        When I post to the materiality assessment "A1" action "complete"
        Then I am redirected to route "company_carbon_footprints_materiality"
        Then I see a text "Omitir"
        Then I see a text "Excluida (recomendación)"
        Then I do not see a text "No aplica a esta huella"
        Then "company" carbon footprint "999" should have source "franchises" excluded in scope "scope_3" with reason "not_significant"
        Then "company" carbon footprint "999" should have source "downstream_leased_assets" excluded in scope "scope_3" with reason "not_significant"
