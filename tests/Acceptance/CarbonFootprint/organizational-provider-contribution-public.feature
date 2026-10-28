Feature:
    I want to make sure that a provider can answer the public contribution link of an organizational carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given property "Hotel" with id "1111" of account "666" exists
        Given property carbon footprint with id "999" for property "1111" of account "666" and year "2025" with scope 3 answered exists
        Given provider "Logística Sur" with id "444" of account "666" exists
        Given provider "444" has a linked user
        Given organizational provider contribution with id "777" for property carbon footprint "999" and provider "444" of account "666" in mode "requested" with token "tok-777" covering "upstream_transport" exists

    Scenario: The contribution link redirects to the general step while there is no consent
        When I go to "/es/carbon-footprint/provider-contribution/tok-777"
        Then I am redirected to route "public_organizational_provider_contribution_general"
        Then the response status should be 200
        Then I see a text "Hotel"
        Then I see a text "2025"

    Scenario: The general step requires the consent
        When I go to "/es/carbon-footprint/provider-contribution/tok-777/general"
        When I submit a form with "companyName:Logística Sur SL" and button "Siguiente"
        Then the response status should be 200
        Then I see a text "Debes aceptar el tratamiento de tus datos para continuar."

    Scenario: Accepting the consent moves on to the emissions step and the link resumes there
        When I go to "/es/carbon-footprint/provider-contribution/tok-777/general"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "companyName:Logística Sur SL|service:Transporte" and button "Siguiente"
        Then I am redirected to route "public_organizational_provider_contribution_emissions"
        Then the response status should be 200
        When I go to "/es/carbon-footprint/provider-contribution/tok-777"
        Then I am redirected to route "public_organizational_provider_contribution_emissions"

    Scenario: Reporting the emissions of the service marks the contribution as answered
        Given organizational provider contribution "777" has consent
        When I go to "/es/carbon-footprint/provider-contribution/tok-777/emissions"
        Then the response status should be 200
        When I submit the provider emissions form in mode "reported" for source "upstream_transport" with rows "Reparto 2025:3200:kg,Última milla:0.8:t"
        Then I am redirected to route "public_organizational_provider_contribution_answered"
        Then the response status should be 200
        Then I see a text "Gracias"
        Then organizational provider contribution "777" should have status "answered"
        Then property carbon footprint "999" should have 2 activities of provider "444" in source "upstream_transport"

    Scenario: Detailing the activity data creates activities of the provider
        Given organizational provider contribution "777" has consent
        Given transport "Camión" with id "RT1" and emission factor for year "2025" exists
        When I go to "/es/carbon-footprint/provider-contribution/tok-777/emissions"
        When I submit the provider emissions form in mode "activities" for source "upstream_transport" with rows "RT1:100:kg"
        Then I am redirected to route "public_organizational_provider_contribution_answered"
        Then organizational provider contribution "777" should have status "answered"
        Then property carbon footprint "999" should have 1 activities of provider "444" in source "upstream_transport"

    Scenario: Only the rows of the chosen mode are kept
        Given organizational provider contribution "777" has consent
        Given transport "Camión" with id "RT1" and emission factor for year "2025" exists
        When I go to "/es/carbon-footprint/provider-contribution/tok-777/emissions"
        When I submit the provider emissions form in mode "reported" for source "upstream_transport" with rows "Reparto 2025:3200:kg"
        When I go to "/es/carbon-footprint/provider-contribution/tok-777/emissions"
        When I submit the provider emissions form in mode "activities" for source "upstream_transport" with rows "RT1:100:kg"
        Then property carbon footprint "999" should have 1 activities of provider "444" in source "upstream_transport"

    Scenario: An invalid token shows the invalid page
        When I go to "/es/carbon-footprint/provider-contribution/this-token-does-not-exist"
        Then the response status should be 404

    Scenario: An ignored contribution shows the invalid page
        Given organizational provider contribution "777" is ignored
        When I go to "/es/carbon-footprint/provider-contribution/tok-777/general"
        Then the response status should be 404

    Scenario: Answering a contribution of a completed footprint is refused
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with providers answered exists
        Given organizational provider contribution with id "778" for property carbon footprint "998" and provider "444" of account "666" in mode "requested" with token "tok-778" covering "upstream_transport" exists
        Given organizational provider contribution "778" has consent
        Given property carbon footprint "998" is completed
        When I post the provider emissions of token "tok-778" in mode "reported" for source "upstream_transport" with rows "Reparto 2025:3200:kg"
        Then the response status should be 409
        Then property carbon footprint "998" should have 0 activities of provider "444" in source "upstream_transport"

    Scenario Outline: The link of a completed footprint shows the closed message instead of the form
        Given property carbon footprint with id "998" for property "1111" of account "666" and year "2024" with providers answered exists
        Given organizational provider contribution with id "778" for property carbon footprint "998" and provider "444" of account "666" in mode "requested" with token "tok-778" covering "upstream_transport" exists
        Given organizational provider contribution "778" has consent
        Given property carbon footprint "998" is completed
        When I go to "<path>"
        Then the response status should be 409
        Then I see a text "Esta huella de carbono ya se ha cerrado"

        Examples:
            | path                                                         |
            | /es/carbon-footprint/provider-contribution/tok-778           |
            | /es/carbon-footprint/provider-contribution/tok-778/general   |
            | /es/carbon-footprint/provider-contribution/tok-778/emissions |

    Scenario: The emissions step without consent redirects to the general step
        When I go to "/es/carbon-footprint/provider-contribution/tok-777/emissions"
        Then I am redirected to route "public_organizational_provider_contribution_general"

    Scenario: Submitting the emissions without choosing the mode of a category is refused
        Given organizational provider contribution "777" has consent
        When I go to "/es/carbon-footprint/provider-contribution/tok-777/emissions"
        When I submit the provider emissions form without choosing the mode for source "upstream_transport" with rows "Reparto 2025:3200:kg"
        Then I am redirected to route "public_organizational_provider_contribution_emissions"
        Then the response status should be 200
        Then I see a text "Elige cómo quieres informar las emisiones de cada categoría."
        Then organizational provider contribution "777" should have status "pending"

    Scenario: The provider calculates the emissions of a row
        When I send a "POST" request to "/es/carbon-footprint/provider-contribution/tok-777/emissions/calculate" with body:
            """
            {"type": "reported_emissions", "itemId": "reported", "unit": "t", "quantity": "1.2"}
            """
        Then the response status should be 200
        Then the response JSON should be similar to:
            """
            {"emissions": {}, "emissionFactor": {}}
            """
        Then I see a text "1200"

    Scenario: Calculating with an invalid token is refused
        When I send a "POST" request to "/es/carbon-footprint/provider-contribution/this-token-does-not-exist/emissions/calculate" with body:
            """
            {"type": "reported_emissions", "itemId": "reported", "unit": "t", "quantity": "1.2"}
            """
        Then the response status should be 404
