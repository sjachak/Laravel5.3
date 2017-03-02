<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BaseCampaign;
use App\Campaign;
use App\User;
use App\Source;
use App\Stage;
use Carbon\Carbon;
use Auth;
use DB;

/**
 * Controller to manage user posts
 */
class PostsController extends Controller
{

    /**
     * Method to show create campaign form
     * @param   \App\baseCampaign\ $baseCampaign model object
     * @return Illuminate\Http\Response
     */
    public function create(POST $post)
    {
        //authorise
        $this->authorize('create', Post::class);

        // Fetch base posts assigned to user
        $userPosts = Auth::user()->posts()->get();

        // Check if any posts are assigned to user
        $userPostsList = array();
        if (!empty($userPosts)) {
            foreach ($userPosts as $key => $value) {
                $userPostsList[$value['id']] = $value['name'];
            }
        }

        return view('campaigns.create', compact('userPostsList'));
    }

    /**
     * Method to show edit post form
     * @param   $id post id
     * @param \App\Campaign $campaign model object
     * @return Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        // authorize user to edit his own post only
        $this->authorize('update', $post);

        $postData = $post->user()->first();
        $userName = '';
        if (!empty( $postData)) {
            $userName = $postData->user_name . " <" . $postData->user_email_address . ">";
        }

        // Fetch all stages that belong to this campaign
        $stages = $campaign->stages()
            ->orderBy('sort_order', 'asc')
            ->get();

        // set datetime 24 hr format into 12 hr datetime format
        foreach ($stages as $key => $value) {
            if ($value['schedule_type'] == 'date') {
                $stages[$key]['scheduleDate'] = date('m/d/Y g:i A', strtotime($value['schedule_value']));
            }
        }

        // Show how many stages completed for this campaign
        $stagesCount = $campaign->stages()
                ->where('status', '!=', Stage::STATUS_PENDING)
                ->count();

        // Show when campaign is ending
        $lastStageData = $campaign->stages()
               // ->where('status', '!=', Stage::STATUS_PENDING)
                ->orderBy('id', 'desc')
                ->take(1)
                ->get()
                ->toArray();

        // Set Stage status if campaign is not having  pending status
        $statusFlag = 0;
        if ($campaign->status != Campaign::STATUS_PENDING) {
            $statusFlag = 1;
        }


        return view('campaigns.edit', ['campaignData' => $campaign,
            'stages' => $stages,
            'sourceName' => $sourceName,
            // set start date in am pm format
            'start_date' => date('m/d/Y g:i A', strtotime($campaign->start_date)),
            'campaignStatus' => $campaign->showStatus($campaign->status),
            'stagesCompleted' => $stagesCount,
            'campaignEndDate' => isset($lastStageData[0]['send_at']) ? date('m/d/Y', strtotime($lastStageData[0]['send_at'])) : "N/A",
            'statusFlag' => $statusFlag
        ]);
    }

    /**
     * Method to download campaigns csv file
     * @param  App\Campaign $campaign model object
     */
    public function download(Campaign $campaign)
    {
        // athorize admin person to export csv
        $this->authorize('download', $campaign);
        // export csv
        if(Auth::user()->isAdmin()) {
            $campaign->export($campaign);
        } else {
            $campaign->export($campaign, Auth::user()->id);
        }

    }
}
