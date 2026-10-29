Feature:
    I want to make sure that a provider can access and answer the "General" step of its public contribution link

    Scenario: A provider can see the event info and its current company data
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider "444" has a linked user
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I go to "/es/carbon-footprint/contribution/token-abc/general"
        Then the response status should be 200
        Then I see a text "para calcular y agregar la huella de carbono del evento"
        Then I see a text "Evento A"
        Then I see a text "Provider Company"

    Scenario: A provider can update its company data and is redirected to the assembly step
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider "444" has a linked user
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I go to "/es/carbon-footprint/contribution/token-abc/general"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "companyName:New Company Name" and button "Siguiente"
        Then I am redirected to route "public_carbon_footprint_contribution_assembly"

    Scenario: The contribution link redirects to the first pending step of the wizard
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider "444" has a linked user
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I go to "/es/carbon-footprint/contribution/token-abc"
        Then I am redirected to route "public_carbon_footprint_contribution_general"

    Scenario: The contribution link resumes the wizard at the step after the last answered one
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider "444" has a linked user
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        When I go to "/es/carbon-footprint/contribution/token-abc/general"
        When I tick the checkbox "consent_accepted"
        When I submit a form with "companyName:New Company Name" and button "Siguiente"
        When I go to "/es/carbon-footprint/contribution/token-abc"
        Then I am redirected to route "public_carbon_footprint_contribution_assembly"

    Scenario: An invalid token shows the invalid page
        When I go to "/es/carbon-footprint/contribution/this-token-does-not-exist/general"
        Then the response status should be 404

    Scenario: An ignored contribution shows the invalid page
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        Given provider contribution with id "111" for event carbon footprint "999" and provider "444" of account "666" with token "token-abc" exists
        Given provider contribution "111" is ignored
        When I go to "/es/carbon-footprint/contribution/token-abc/general"
        Then the response status should be 404
