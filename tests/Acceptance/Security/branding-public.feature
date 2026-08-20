Feature:
    I want the public pages of an account to render with that account's branding

    Scenario: A public survey of an account with branding carries its palette
        Given account "MyCompany" with id "666" exists
        Given account "666" has branding with primary colour "#8A2432"
        Given event "Evento A" with id "333" of account "666" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        Then the response status should be 200
        Then I see a text "body.branded-page{ --brand-primary:#8A2432"
        Then I see a text "--brand-primary-hover:#751F2B"
        Then I see a text "family=Poppins:wght@400;700"

    Scenario: A public survey of an account without branding keeps the platform palette
        Given account "MyCompany" with id "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        Then the response status should be 200
        Then I see a text "--brand-primary:#3E7490"
        Then I see a text "family=Inter"

    Scenario: Branded public pages are locked to the light theme and hide the theme switch
        Given account "MyCompany" with id "666" exists
        Given account "666" has branding with primary colour "#8A2432"
        Given event "Evento A" with id "333" of account "666" exists
        Given attendee survey with id "AS1" and token "token-abc" for event "333" of account "666" exists
        Given attendee survey "AS1" has venue location "IFEMA, Madrid" at "40.4680", "-3.6160"
        When I go to "/es/carbon-footprint/attendee-survey/token-abc/intercity"
        Then I see a text 'data-bs-theme="light"'
        Then I see a text 'data-bs-theme-locked="true"'
        Then I do not see a text "dark-layout"

    Scenario: The backoffice never receives the branded-page class
        Given user "admin" with role "ROLE_ADMIN" exists
        When I am logged as "admin"
        When I go to "/es/"
        Then the response status should be 200
        Then I do not see a text "branded-page"

    Scenario: The signature flow is deliberately left out of the white label
        Given account "MyCompany" with id "mycompany" exists
        Given account "mycompany" has branding with primary colour "#8A2432"
        Given signature request with id "sign-111" and account "mycompany" and token "sign-token-aaa" exists
        When I go to "/es/sign/sign-token-aaa"
        Then the response status should be 200
        Then I do not see a text "#8A2432"
        Then I do not see a text "family=Poppins"
