# Tech Assessment Tasks
These tasks are meant to be a simulation to assess how you would work to resolve potential issues and feature requests coming to the team. README files and various design documents are available in this repository to guide you with how the app works


## Tasks
### Task 1 - Fixing Filters
`Hospital Cover` filter is meant to be a multi-select option. However, when you select more than one option from the available values, we get no results. Fix the filter so products that fit in any of the possible covers selected are shown.

Acceptance Criteria:
- User can select more than 1 option in Hospital Cover filter
- Products that match the options and other filters are shown.

### Task 2 - Expander state
Each product card has an accordion/expandable section for `Hospital`, `Extras`, and `Ambulance` cover. Clicking on a product card's accordion only toggles that section for that card, making comparison difficult. We want all product cards to have the same expanded/collapsed sections at all times.

Acceptance Criteria:
- When user expands `Extras` on one product card, all other cards would have `Extras` expanded.
- When user expands `Hospital` on one product card, all other cards would have `Hospital` expanded.
- When user expands `Ambulance` on one product card, all other cards would have `Ambulance` expanded.
- Expanding one section collapses all other sections. Only one active section at a time.